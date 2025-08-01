<?php

namespace App\Models;

use App\Enums\GenderType;
use App\Enums\ReligionType;
use App\Enums\SegmentType;
use App\Enums\StatusType;
use App\Interfaces\WithPeriodPivot;
use App\Traits\HasPeriodRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use stdClass;

class Employee extends Model implements WithPeriodPivot
{
  use HasFactory, Notifiable, HasPeriodRelation;

  /**
   * The attributes that are mass assignable.
   *
   * @var list<string>
   */
  protected $fillable = [
    'name',
    'email',
    'phone',
    'address',
    'birthdate',
    'gender',
    'religion',
    'status',
    'department_id',
    'position_id',
  ];

  /**
   * The attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'birthdate' =>  'date',
      'status' => StatusType::class,
      'religion' => ReligionType::class,
      'gender' => GenderType::class,
    ];
  }

  /**
   * get employee matrix data
   * 
   * @return object
   */
  public function matrix(): stdClass
  {
    $this->load('department', 'position');

    $DEFAULT = 0;
    $matrix = new stdClass;

    $matrix->training_count = $this->trainings->count();
    $matrix->evaluation_count = $this->evaluations->count();

    $matrix->feedback_score = $this->feedback->average ?? $DEFAULT;
    $matrix->training_score = $this->trainings->avg('pivot.score');
    $matrix->evaluation_score = $this->evaluations->map(fn($evaluation) => ($evaluation->pivot->score / $evaluation->target) * 100)->avg() ?? $DEFAULT;

    $matrix->performance_score = collect([$matrix->evaluation_score])->filter(fn($score) => $score !== null)->avg();
    $matrix->potential_score = collect([$matrix->feedback_score, $matrix->training_score])->filter(fn($score) => $score !== null)->avg();
    $matrix->average_score = collect([$matrix->potential_score, $matrix->performance_score])->filter(fn($score) => $score !== null)->avg();

    $matrix->segment = SegmentType::getSegment(
      $matrix->potential_score,
      $matrix->performance_score
    );

    return $matrix;
  }

  /**
   * Relationship with between model and other model.
   * 
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function department(): BelongsTo
  {
    return $this->belongsTo(Department::class);
  }

  /**
   * Relationship with between model and other model.
   * 
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function position(): BelongsTo
  {
    return $this->belongsTo(Position::class);
  }

  /**
   * The evaluations that belong to the employee.
   * 
   * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
   */
  public function evaluations(): BelongsToMany
  {
    return $this->belongsToManyWithPeriod(Evaluation::class, 'employee_evaluations')
      ->withPivot('score');
  }

  /**
   * The feedback that belong to the employee.
   * 
   * @return \Illuminate\Database\Eloquent\Relations\HasOne
   */
  public function feedback(): HasOne
  {
    return $this->hasOne(Feedback::class)
      ->withDefault([
        'teamwork' => 0,
        'communication' => 0,
        'initiative' => 0,
        'problem_solving' => 0,
        'adaptability' => 0,
        'talent' => 0,
        'description' => 'No feedback available',
      ]);
  }

  /**
   * The trainings that belong to the employee.
   * 
   * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
   */
  public function trainings(): BelongsToMany
  {
    return $this->belongsToManyWithPeriod(Training::class, 'employee_trainings')
      ->withPivot('score', 'email_sent');
  }

  /**
   * The talent trainings that belong to the employee.
   * 
   * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
   */
  public function talents(): BelongsToMany
  {
    return $this->belongsToMany(TalentTraining::class, 'employee_talent_trainings')
      ->withPivot('score', 'email_sent', 'notes');
  }
}
