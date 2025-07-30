<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\Employee;
use App\Models\Position;
use App\Enums\StatusType;
use Illuminate\View\View;
use App\Models\Attachment;
use App\Models\Department;
use App\Models\Evaluation;
use App\Enums\TrainingType;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;
use Illuminate\Http\Request;
use App\Enums\AssignmentType;
use App\Enums\CompletionStatus;
use App\Exports\TrainingExport;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreTrainingRequest;
use App\Notifications\TrainingNotification;
use Maatwebsite\Excel\Excel as ExcelFormat;
use App\Http\Requests\UpdateTrainingRequest;
use App\Http\Requests\AttachmentUploadRequest;

class TrainingController extends Controller
{
  /**
   * Create a new controller instance.
   */
  public function __construct()
  {
    $this->authorizeResource(Training::class);
  }

  /**
   * Display a listing of the resource.
   */
  public function index(Request $request): View
  {
    $search = $request->input('search');
    $assignment = $request->input('assignment');
    $status = $request->input('status');
    $type = $request->input('type');

    $min_date = $request->input('min_date');
    $max_date = $request->input('max_date');

    $trainings = Training::query()
      ->with(['departments', 'evaluation'])
      ->when($search, function ($q) use ($search) {
        $q->where('name', 'like', '%' . $search . '%')
          ->orWhere('description', 'like', '%' . $search . '%');
      })
      ->when($type, function ($q) use ($type) {
        $q->where('type', $type);
      })
      ->when($status, function ($q) use ($status) {
        $q->withStatus($status);
      })
      ->when($assignment, function ($q) use ($assignment) {
        $q->where('assignment', $assignment);
      })
      ->when($min_date, function ($q) use ($min_date) {
        $q->where('start_date', '>=', $min_date);
      })
      ->when($max_date, function ($q) use ($max_date) {
        $q->where('start_date', '<=', $max_date);
      })
      ->orderBy('start_date', 'asc')
      ->paginate(5)
      ->withQueryString();

    return view('dashboard.trainings.index', [
      'trainings' => $trainings,
      'types' => TrainingType::cases(),
      'statuses' => CompletionStatus::cases(),
      'assignments' => AssignmentType::cases(),
    ]);
  }

  /** 
   * Export the trainings into csv file.
   */
  public function export(Request $request)
  {
    return Excel::download(new TrainingExport, 'trainings.csv', ExcelFormat::CSV);
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create()
  {
    return view('dashboard.trainings.create', [
      'departments' => Department::select(['name', 'id'])->get(),
      'evaluations' => Evaluation::select(['name', 'id'])->get(),
      'types' => TrainingType::cases(),
      'assignments' => AssignmentType::cases(),
    ]);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(StoreTrainingRequest $request)
  {
    $validated = $request->validated();
    $department_ids = $validated['department_ids'] ?? [];
    unset($validated['department_ids']);

    $training = Training::create($validated);
    if ($training->assignment === AssignmentType::CLOSED) $training->departments()->attach($department_ids);

    return redirect()
      ->route('trainings.show', $training)
      ->with('success', 'Training created successfully.');
  }

  /**
   * Display the specified resource.
   */
  public function show(Request $request, Training $training)
  {
    $search = $request->input('search');
    $position_id = $request->input('position_id');
    $department_id = $request->input('department_id');

    $assigned = $training->employees()
      ->with(['department', 'position'])
      ->get();

    $employees = Employee::with(['department', 'position'])
      ->when($training->assignment === AssignmentType::CLOSED, function ($q) use ($training) {
        $q->whereIn('department_id', $training->departments->pluck('id'));
      })
      ->where('status', StatusType::ACTIVE->value)
      ->when($search, function ($q) use ($search) {
        $q->where('name', 'like', '%' . $search . '%')
          ->orWhere('email', 'like', '%' . $search . '%');
      })
      ->when($position_id, function ($q) use ($position_id) {
        $q->where('position_id', $position_id);
      })
      ->when($department_id, function ($q) use ($department_id) {
        $q->where('department_id', $department_id);
      })
      ->whereNotIn('id', $assigned->pluck('id'))
      ->paginate(5);

    return view('dashboard.trainings.show', [
      'training' => $training->load('departments'),
      'departments' => Department::select(['name', 'id'])->get(),
      'positions' => Position::select(['name', 'id'])->get(),
      'assigned' => $assigned,
      'employees' => $employees,
    ]);
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(Training $training)
  {
    return view('dashboard.trainings.edit', [
      'training' => $training->load(['departments', 'evaluation']),
      'departments' => Department::select(['name', 'id'])->get(),
      'evaluations' => Evaluation::select(['name', 'id'])->get(),
      'types' => TrainingType::cases(),
      'assignments' => AssignmentType::cases(),
    ]);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(UpdateTrainingRequest $request, Training $training)
  {
    $validated = $request->validated();
    $department_ids = $validated['department_ids'] ?? [];
    unset($validated['department_ids']);

    $training->update($validated);
    if ($training->assignment === AssignmentType::CLOSED) $training->departments()->sync($department_ids);

    return redirect()
      ->route('trainings.show', $training)
      ->with('success', 'Training updated successfully.');
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Training $training)
  {
    $training->delete();

    return redirect()
      ->route('trainings.index')
      ->with('success', 'Training deleted successfully.');
  }

  /**
   * Assign an employee to an training.
   */
  public function assign(Request $request, Training $training): RedirectResponse
  {
    $full = $training->employees->count() >= $training->capacity;
    if ($full) return back()->with('error', 'Training capacity reached!');

    $period = session('period_id');

    $validated = $request->validate([
      'employee_id' => [
        'required',
        'exists:employees,id',
        Rule::unique('employee_trainings')->where(function ($query) use ($training, $period) {
          return $query->where('training_id', $training->id)->where('period_id', $period);
        }),
      ],
    ]);

    $id = $validated['employee_id'];
    $training->employees()->attach($id, [
      'score' => 0,
      'email_sent' => false,
      'period_id' => $period
    ]);

    return back()->with('success', 'Employee assigned successfully!');
  }

  /**
   * Unassign an employee from an training.
   */
  public function unassign(Training $training, Employee $employee): RedirectResponse
  {
    $training->employees()->detach($employee->id);
    return back()->with('success', 'Employee unassigned successfully!');
  }

  /**
   * Notify employees about the training.
   */
  public function notify(Training $training): RedirectResponse
  {
    if ($training->employees->isEmpty()) return back()->with('warning', 'No employees assigned to this training yet!');

    $training->notified = true;
    $training->save();

    $training->employees->each(function ($employee) use ($training) {
      $employee->notify(new TrainingNotification($training, $employee));
      $employee->pivot->email_sent = true;
      $employee->pivot->save();
    });

    return back()->with('success', 'Employees notified successfully!');
  }

  /**
   * Change employee score for a given training.
   */
  public function score(Request $request, Training $training): RedirectResponse
  {
    $employee_ids = $training->employees->pluck('id')->toArray();

    $validated = $request->validate([
      'scores' => ['required', 'array'],
      'scores.*' => [
        'required',
        'integer',
        'between:0,100',
        function ($attribute, $value, $fail) use ($employee_ids) {
          $id = Str::after($attribute, 'scores.');
          if (!in_array($id, $employee_ids)) $fail("Employee not assigned to this training!");
        }
      ],
    ]);

    foreach ($validated['scores'] as $id => $score) {
      $training->employees()->updateExistingPivot($id, [
        'score' => $score
      ]);
    }

    return back()->with('success', "Employee score updated successfully!");
  }

  /**
   * Display the training materials.
   */
  public function material(Request $request, Training $training)
  {
    $assigned = $training->employees()
      ->with('department', 'position')
      ->get();

    return view('dashboard.trainings.material', [
      'training' => $training,
      'assigned' => $assigned,
      'attachments' => $training->attachments,
    ]);
  }

  /**
   * Upload a training material.
   */
  public function upload(AttachmentUploadRequest $request, Training $training)
  {
    $files = $request->file('files');
    $uploaded = collect($files)->map(function ($file) {
      $url = $file->store('uploads', 'public');

      return [
        'url' => asset($url),
        'size' => $file->getSize(),
        'filename' => $file->getClientOriginalName(),
        'mime_type' => $file->getMimeType(),
      ];
    });

    $training->attachments()->createMany($uploaded);

    return redirect()
      ->route('trainings.material', $training)
      ->with('success', 'Training material uploaded successfully!');
  }

  /**
   * Remove a training material.
   */
  public function remove(Training $training, Attachment $attachment)
  {
    $uri = Uri::of($attachment->url);
    $path = $uri->path();
    $exist = Storage::disk('public')->exists($path);
    if ($exist) Storage::disk('public')->delete($path);

    $attachment->delete();

    return redirect()
      ->route('trainings.material', $training)
      ->with('success', 'Training material removed successfully!');
  }
}
