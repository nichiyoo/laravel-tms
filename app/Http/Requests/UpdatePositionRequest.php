<?php

namespace App\Http\Requests;

use App\Enums\LevelType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
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
      'name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('positions')->ignore($this->position)
      ],
      'description' => ['required', 'string', 'max:320'],
      'level' => ['required', Rule::enum(LevelType::class)],
      'requirements' => ['required', 'array'],
      'requirements.*' => ['string', 'max:255'],
    ];
  }
}
