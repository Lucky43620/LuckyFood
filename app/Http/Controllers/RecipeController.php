<?php

namespace App\Http\Controllers;

use App\Http\Integrations\FatSecret\FatSecretErrorPresenter;
use App\Http\Requests\SearchRecipeIngredientsRequest;
use App\Http\Requests\StoreRecipeRequest;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Services\FatSecretService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    public function __construct(
        private readonly FatSecretService $fatSecret,
        private readonly FatSecretErrorPresenter $errors,
    ) {}

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

    public function searchIngredients(SearchRecipeIngredientsRequest $request): JsonResponse
    {
        $query = $request->queryText();

        if (strlen($query) < 2) {
            return response()->json([
                'results' => [],
                'error' => null,
            ]);
        }

        $search = $this->fatSecret->searchFoodsPageResult(
            $query,
            0,
            10,
            $request->user()?->fatsecret_region,
            $request->user()?->fatsecret_language,
        );

        return response()->json([
            'results' => array_values(array_map(
                static fn (array $food): array => [
                    'food_id' => (string) ($food['food_id'] ?? ''),
                    'food_name' => (string) ($food['food_name'] ?? ''),
                    'serving_description' => (string) ($food['serving_description'] ?? ''),
                    'calories' => (float) ($food['calories'] ?? 0),
                    'protein' => (float) ($food['protein'] ?? 0),
                    'carbs' => (float) ($food['carbs'] ?? 0),
                    'fat' => (float) ($food['fat'] ?? 0),
                ],
                $search->data()['results'],
            )),
            'error' => $this->errors->ingredient($search->error()),
        ]);
    }

    public function store(StoreRecipeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated): void {
            $recipe = Recipe::create([
                'user_id' => $request->user()->id,
                'name' => $validated['name'],
                'servings' => $validated['servings'],
                'prep_time' => $validated['prep_time'],
                'tags' => $validated['tags'] ?? [],
                'instructions' => $this->normalizeInstructions($validated['instructions'] ?? []),
                'total_calories' => $validated['total_calories'],
                'total_protein' => $validated['total_protein'] ?? 0,
                'total_carbs' => $validated['total_carbs'] ?? 0,
                'total_fat' => $validated['total_fat'] ?? 0,
            ]);

            $ingredients = array_map(fn (array $ing): array => [
                'recipe_id' => $recipe->id,
                'food_id' => $ing['food_id'],
                'food_name' => $ing['food_name'],
                'quantity' => $ing['quantity'],
                'unit' => $ing['unit'] ?? 'g',
                'calories' => $ing['calories'] ?? 0,
                'protein' => $ing['protein'] ?? 0,
                'carbs' => $ing['carbs'] ?? 0,
                'fat' => $ing['fat'] ?? 0,
            ], $validated['ingredients']);

            RecipeIngredient::insert($ingredients);
        });

        return redirect()->route('recipes.index');
    }

    public function show(Recipe $recipe): Response
    {
        Gate::authorize('view', $recipe);

        return Inertia::render('Recettes/Show', [
            'recipe' => $recipe->load('ingredients'),
            'perServing' => [
                'calories' => $recipe->caloriesPerServing(),
                'protein' => $recipe->servings > 0 ? round($recipe->total_protein / $recipe->servings, 1) : 0,
                'carbs' => $recipe->servings > 0 ? round($recipe->total_carbs / $recipe->servings, 1) : 0,
                'fat' => $recipe->servings > 0 ? round($recipe->total_fat / $recipe->servings, 1) : 0,
            ],
        ]);
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        Gate::authorize('delete', $recipe);
        $recipe->delete();

        return back();
    }

    private function normalizeInstructions(array $instructions): array
    {
        return array_values(array_filter(array_map(
            static fn (mixed $instruction): string => trim((string) $instruction),
            $instructions,
        )));
    }
}
