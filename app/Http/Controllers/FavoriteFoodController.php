<?php

namespace App\Http\Controllers;

use App\Models\FavoriteFood;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteFoodController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'food_id' => ['required', 'string', 'max:255'],
            'food_name' => ['required', 'string', 'max:255'],
            'serving_description' => ['nullable', 'string', 'max:255'],
            'calories' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'protein' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'carbs' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'fat' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'fiber' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ]);

        FavoriteFood::updateOrCreate(
            ['user_id' => $request->user()->id, 'food_id' => $validated['food_id']],
            [
                'food_name' => $validated['food_name'],
                'serving_description' => $validated['serving_description'] ?? null,
                'calories' => (int) round((float) ($validated['calories'] ?? 0)),
                'protein' => $validated['protein'] ?? 0,
                'carbs' => $validated['carbs'] ?? 0,
                'fat' => $validated['fat'] ?? 0,
                'fiber' => $validated['fiber'] ?? 0,
            ],
        );

        return back();
    }

    public function destroy(Request $request, string $foodId): RedirectResponse
    {
        FavoriteFood::where('user_id', $request->user()->id)
            ->where('food_id', $foodId)
            ->delete();

        return back();
    }
}
