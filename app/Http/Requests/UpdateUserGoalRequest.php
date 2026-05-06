<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'calories_goal' => ['required', 'integer', 'min:500', 'max:10000'],
            'protein_goal' => ['required', 'integer', 'min:0', 'max:500'],
            'carbs_goal' => ['required', 'integer', 'min:0', 'max:1000'],
            'fat_goal' => ['required', 'integer', 'min:0', 'max:300'],
            'fiber_goal' => ['nullable', 'integer', 'min:0', 'max:200'],
            'water_goal' => ['nullable', 'integer', 'min:0', 'max:30'],
            'weight_current' => ['nullable', 'numeric', 'min:20', 'max:500'],
            'weight_goal' => ['nullable', 'numeric', 'min:20', 'max:500'],
            'activity_level' => ['required', Rule::in(['sedentary', 'light', 'moderate', 'active', 'very_active'])],
            'goal_type' => ['required', Rule::in(['lose', 'maintain', 'gain'])],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'age' => ['nullable', 'integer', 'min:10', 'max:120'],
            'height_cm' => ['nullable', 'integer', 'min:80', 'max:250'],
        ];
    }
}
