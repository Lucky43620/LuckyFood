<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecipeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'servings' => ['required', 'integer', 'min:1', 'max:100'],
            'prep_time' => ['required', 'integer', 'min:0', 'max:1440'],
            'is_public' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:100'],
            'instructions' => ['nullable', 'array', 'max:50'],
            'instructions.*' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'total_calories' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'total_protein' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'total_carbs' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'total_fat' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'ingredients' => ['required', 'array', 'min:1', 'max:100'],
            'ingredients.*.food_id' => ['required', 'string', 'max:255'],
            'ingredients.*.food_name' => ['required', 'string', 'max:255'],
            'ingredients.*.quantity' => ['required', 'numeric', 'min:0.1', 'max:100000'],
            'ingredients.*.unit' => ['nullable', 'string', 'max:50'],
            'ingredients.*.calories' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'ingredients.*.protein' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'ingredients.*.carbs' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'ingredients.*.fat' => ['nullable', 'numeric', 'min:0', 'max:10000'],
        ];
    }
}
