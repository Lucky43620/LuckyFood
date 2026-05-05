<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    public function index(Request $request): Response
    {
        $recipes = Recipe::where('user_id', $request->user()->id)
            ->withCount('ingredients')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Recettes/Index', [
            'recipes' => $recipes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Recettes/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'servings'       => 'required|integer|min:1',
            'prep_time'      => 'required|integer|min:0',
            'tags'           => 'nullable|array',
            'tags.*'         => 'string|max:100',
            'total_calories' => 'required|integer|min:0',
            'total_protein'  => 'nullable|numeric|min:0',
            'total_carbs'    => 'nullable|numeric|min:0',
            'total_fat'      => 'nullable|numeric|min:0',
            'ingredients'    => 'required|array|min:1',
            'ingredients.*.food_id'   => 'required|string',
            'ingredients.*.food_name' => 'required|string',
            'ingredients.*.quantity'  => 'required|numeric|min:0',
            'ingredients.*.unit'      => 'nullable|string',
            'ingredients.*.calories'  => 'nullable|integer|min:0',
            'ingredients.*.protein'   => 'nullable|numeric|min:0',
            'ingredients.*.carbs'     => 'nullable|numeric|min:0',
            'ingredients.*.fat'       => 'nullable|numeric|min:0',
        ]);

        $recipe = Recipe::create([
            'user_id'        => $request->user()->id,
            'name'           => $validated['name'],
            'servings'       => $validated['servings'],
            'prep_time'      => $validated['prep_time'],
            'tags'           => $validated['tags'] ?? [],
            'total_calories' => $validated['total_calories'],
            'total_protein'  => $validated['total_protein'] ?? 0,
            'total_carbs'    => $validated['total_carbs'] ?? 0,
            'total_fat'      => $validated['total_fat'] ?? 0,
        ]);

        $ingredients = array_map(fn($ing) => [
            'recipe_id' => $recipe->id,
            'food_id'   => $ing['food_id'],
            'food_name' => $ing['food_name'],
            'quantity'  => $ing['quantity'],
            'unit'      => $ing['unit'] ?? 'g',
            'calories'  => $ing['calories'] ?? 0,
            'protein'   => $ing['protein'] ?? 0,
            'carbs'     => $ing['carbs'] ?? 0,
            'fat'       => $ing['fat'] ?? 0,
        ], $validated['ingredients']);

        RecipeIngredient::insert($ingredients);

        return redirect()->route('recipes.index');
    }

    public function show(Recipe $recipe): Response
    {
        abort_unless($recipe->user_id === request()->user()->id, 403);

        return Inertia::render('Recettes/Show', [
            'recipe'      => $recipe->load('ingredients'),
            'perServing'  => [
                'calories' => $recipe->caloriesPerServing(),
                'protein'  => $recipe->servings > 0 ? round($recipe->total_protein / $recipe->servings, 1) : 0,
                'carbs'    => $recipe->servings > 0 ? round($recipe->total_carbs   / $recipe->servings, 1) : 0,
                'fat'      => $recipe->servings > 0 ? round($recipe->total_fat     / $recipe->servings, 1) : 0,
            ],
        ]);
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        abort_unless($recipe->user_id === request()->user()->id, 403);
        $recipe->delete();

        return back();
    }
}
