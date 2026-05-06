<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFoodDiaryEntryRequest extends FormRequest
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
            'date' => ['nullable', 'date_format:Y-m-d'],
            'food_id' => ['required', 'string', 'max:255'],
            'food_name' => ['required', 'string', 'max:255'],
            'meal_type' => ['required', Rule::in(['breakfast', 'lunch', 'snack', 'dinner'])],
            'calories' => ['required', 'numeric', 'min:0', 'max:10000'],
            'protein' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'carbs' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'fat' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'fiber' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'serving_description' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0.1', 'max:10000'],
        ];
    }

    public function entryDate(): string
    {
        return (string) $this->input('date', now()->toDateString());
    }
}
