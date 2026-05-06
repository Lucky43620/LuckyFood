<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowFoodRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:120'],
            'meal' => ['nullable', Rule::in(['breakfast', 'lunch', 'snack', 'dinner'])],
            'from' => ['nullable', Rule::in(['journal'])],
        ];
    }

    public function meal(): string
    {
        return (string) $this->input('meal', 'breakfast');
    }

    public function queryText(): string
    {
        return trim((string) $this->input('q', ''));
    }

    public function from(): ?string
    {
        return $this->input('from');
    }
}
