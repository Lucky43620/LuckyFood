<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BarcodeFoodRequest extends FormRequest
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
            'barcode' => ['nullable', 'string', 'regex:/^[0-9]{6,32}$/'],
            'meal' => ['nullable', Rule::in(['breakfast', 'lunch', 'snack', 'dinner'])],
        ];
    }

    public function barcode(): string
    {
        return trim((string) $this->input('barcode', ''));
    }

    public function meal(): string
    {
        return (string) $this->input('meal', 'breakfast');
    }
}
