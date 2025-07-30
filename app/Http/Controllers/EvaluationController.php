<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Employee;
use App\Models\Position;
use App\Enums\StatusType;
use Illuminate\View\View;
use App\Models\Evaluation;
use App\Models\Department;
use App\Enums\ApprovalType;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Exports\SummaryExport;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Enum;
use Maatwebsite\Excel\Excel as ExcelFormat;
use App\Http\Requests\StoreEvaluationRequest;
use App\Http\Requests\UpdateEvaluationRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class EvaluationController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->authorizeResource(Evaluation::class);
  }

  /**
   * Display a listing of the resource.
   */
  public function index(Request $request): View
  {
    $search = $request->input('search');
    $department_id = $request->input('department_id');
    $topic_id = $request->input('topic_id');
    $status = $request->input('status');

    $evaluations = Evaluation::query()
      ->with(['department', 'topic', 'position'])
      ->when($search, function ($q) use ($search) {
        $q->where('name', 'like', '%' . $search . '%')->orWhere('description', 'like', '%' . $search . '%');
      })
      ->when($department_id, function ($q) use ($department_id) {
        $q->where('department_id', $department_id);
      })
      ->when($topic_id, function ($q) use ($topic_id) {
        $q->where('topic_id', $topic_id);
      })
      ->when($status, function ($q) use ($status) {
        $q->where('status', $status);
      })
      ->paginate(5)
      ->withQueryString();

    return view('dashboard.evaluations.index', [
      'evaluations' => $evaluations,
      'departments' => Department::select(['name', 'id'])->get(),
      'topics' => Topic::select(['name', 'id'])->get(),
      'statuses' => ApprovalType::cases()
    ]);
  }

  /** 
   * Export the evaluations into csv file.
   */
  public function export(Request $request)
  {
    return Excel::download(new SummaryExport, 'evaluations.csv', ExcelFormat::CSV);
  }

  /**
   * Show the summary of the evaluation.
   */
  public function summary(Request $request): View
  {
    $page = $request->get('page', 1);
    $per_page = $request->get('per_page', 5);
    $topic_id = $request->input('topic_id');
    $department_id = $request->input('department_id');

    $topics = Topic::get();
    $departments = Department::get();
    $employees = Employee::with(['department', 'position', 'feedback', 'trainings', 'evaluations'])
      ->when($department_id, function ($q) use ($department_id) {
        $q->where('department_id', $department_id);
      })
      ->when($topic_id, function ($q) use ($topic_id) {
        $q->whereHas('evaluations', function ($q) use ($topic_id) {
          $q->where('topic_id', $topic_id);
        });
      })
      ->get()
      ->map(function ($employee) {
        $employee->matrix = $employee->matrix();
        return $employee;
      });

    $summary = $employees->map(function ($employee) {
      $employee->average_score = $employee->matrix->average_score;
      $employee->average_potential = $employee->matrix->potential_score;
      $employee->average_performance = $employee->matrix->performance_score;
      return $employee;
    });

    $chart = (object) [
      'departments' => $departments->map(function ($department) use ($summary) {
        return [
          'label' => $department->name,
          'average_score' => $summary->where('department_id', $department->id)->avg('average_score') ?? 0
        ];
      }),
      'topics' => $topics->map(function ($topic) use ($summary) {
        return [
          'label' => $topic->name,
          'average_score' => $summary->where('topic_id', $topic->id)->avg('average_score') ?? 0
        ];
      })
    ];

    $top_performers = new LengthAwarePaginator(
      $employees->sortByDesc('matrix.average_score')->forPage($page, $per_page),
      $employees->count(),
      $per_page,
      $page,
      ['path' => request()->url(), 'query' => request()->query()]
    );

    return view('dashboard.evaluations.summary', [
      'departments' => $departments,
      'topics' => $topics,
      'chart' => $chart,
      'top_performers' => $top_performers,
      'average_score' => $summary->avg('average_score'),
      'average_potential' => $summary->avg('average_potential'),
      'average_performance' => $summary->avg('average_performance'),
      'evaluation_count' => $summary->count(),
    ]);
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create(): View
  {
    return view('dashboard.evaluations.create', [
      'departments' => Department::select(['name', 'id'])->get(),
      'positions' => Position::select(['name', 'id'])->get(),
      'topics' => Topic::select(['name', 'id'])->get(),
    ]);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(StoreEvaluationRequest $request): RedirectResponse
  {
    $validated = $request->validated();
    $evaluation = Evaluation::create($validated);

    return redirect()
      ->route('evaluations.show', $evaluation)
      ->with('success', 'Evaluation created successfully!');
  }

  /**
   * Display the specified resource.
   */
  public function show(Request $request, Evaluation $evaluation): View
  {
    $search = $request->input('search');

    $assigned = $evaluation->employees()
      ->with(['department', 'position'])
      ->get();

    $assigned_ids = $assigned->pluck('id')->toArray();

    $employees = Employee::with(['department', 'position'])
      ->where('department_id', $evaluation->department_id)
      ->where('position_id', $evaluation->position_id)
      ->where('status', StatusType::ACTIVE->value)
      ->when($search, function ($q) use ($search) {
        $q->where(function ($query) use ($search) {
          $query->where('name', 'like', '%' . $search . '%')->orWhere('email', 'like', '%' . $search . '%');
        });
      })
      ->whereNotIn('id', $assigned_ids)
      ->paginate(5);

    return view('dashboard.evaluations.show', [
      'evaluation' => $evaluation,
      'assigned' => $assigned,
      'employees' => $employees,
    ]);
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(Evaluation $evaluation): View
  {
    return view('dashboard.evaluations.edit', [
      'evaluation' => $evaluation->load(['department', 'topic']),
      'departments' => Department::select('id', 'name')->get(),
      'positions' => Position::select('id', 'name')->get(),
      'topics' => Topic::select('id', 'name')->get(),
    ]);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(UpdateEvaluationRequest $request, Evaluation $evaluation): RedirectResponse
  {
    $validated = $request->validated();

    $evaluation->update([
      ...$validated,
      'status' => ApprovalType::DRAFT,
    ]);

    $evaluation->employees()->detach();

    return redirect()
      ->route('evaluations.show', $evaluation)
      ->with('success', 'Evaluation updated successfully!');
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Evaluation $evaluation): RedirectResponse
  {
    $evaluation->delete();

    return redirect()
      ->route('evaluations.index')
      ->with('success', 'Evaluation deleted successfully!');
  }

  /**
   * Assign an employee to an evaluation.
   */
  public function assign(Request $request, Evaluation $evaluation): RedirectResponse
  {
    $period = session('period_id');

    $validated = $request->validate([
      'employee_id' => [
        'required',
        'exists:employees,id',
        Rule::unique('employee_evaluations')->where(function ($query) use ($evaluation, $period) {
          return $query->where('evaluation_id', $evaluation->id)->where('period_id', $period);
        }),
      ],
    ]);

    $id = $validated['employee_id'];
    $evaluation->employees()->attach($id, [
      'score' => 0,
      'period_id' => $period
    ]);

    return back()->with('success', 'Employee assigned successfully!');
  }

  /**
   * Unassign an employee from an evaluation.
   */
  public function unassign(Evaluation $evaluation, Employee $employee): RedirectResponse
  {
    $evaluation->employees()->detach($employee->id);

    return back()->with('success', 'Employee unassigned successfully!');
  }

  /**
   * Change the approval status of an evaluation.
   */
  public function approval(Request $request, Evaluation $evaluation): RedirectResponse
  {
    $validated = $request->validate([
      'status' => [
        'required',
        'string',
        new Enum(ApprovalType::class),
      ],
    ]);

    $status = $validated['status'];
    $evaluation->status = ApprovalType::from($status);
    $evaluation->save();

    if ($status !== ApprovalType::APPROVED->value) $evaluation->employees()->detach();

    return back()->with('success', "Evaluation status updated successfully!");
  }

  /**
   * Change employee score for a given evaluation.
   */
  public function score(Request $request, Evaluation $evaluation): RedirectResponse
  {
    $employee_ids = $evaluation->employees->pluck('id')->toArray();

    $validated = $request->validate([
      'scores' => ['required', 'array'],
      'scores.*' => [
        'required',
        'integer',
        'between:0,' . $evaluation->target,
        function ($attribute, $value, $fail) use ($employee_ids) {
          $id = Str::after($attribute, 'scores.');
          if (!in_array($id, $employee_ids)) $fail("Employee not assigned to this evaluation!");
        }
      ],
    ]);

    foreach ($validated['scores'] as $id => $score) {
      $evaluation->employees()->updateExistingPivot($id, [
        'score' => $score
      ]);
    }

    return back()->with('success', "Employee score updated successfully!");
  }
}
