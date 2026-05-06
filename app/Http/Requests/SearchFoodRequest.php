<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchFoodRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:0', 'max:500'],
            'category_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function queryText(): string
    {
        return trim((string) $this->input('q', ''));
    }

    public function meal(): string
    {
        return (string) $this->input('meal', 'breakfast');
    }

    public function pageNumber(): int
    {
        return max(0, $this->integer('page', 0));
    }

    public function categoryId(): ?int
    {
        return $this->integer('category_id') ?: null;
    }
}
