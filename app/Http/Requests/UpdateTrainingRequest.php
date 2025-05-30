<?php

namespace App\Http\Requests;

use App\Enums\TrainingType;
use App\Enums\AssignmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTrainingRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'name' => ['required', 'string', 'max:255'],
      'description' => ['required', 'string'],
      'department_ids' => ['required_if:assignment,multiple', 'array'],
      'department_ids.*' => ['exists:departments,id'],
      'evaluation_id' => ['nullable', 'integer', 'exists:evaluations,id'],
      'start_date' => ['required', 'date'],
      'end_date' => ['required', 'date', 'after:start_date'],
      'duration' => ['required', 'integer', 'min:0'],
      'capacity' => ['required', 'integer', 'min:0'],
      'type' => ['required', new Enum(TrainingType::class)],
      'assignment' => ['required', new Enum(AssignmentType::class)],
    ];
  }
}
