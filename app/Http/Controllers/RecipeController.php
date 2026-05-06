<?php

namespace App\Http\Controllers;

use App\Http\Integrations\FatSecret\FatSecretErrorPresenter;
use App\Http\Requests\SearchRecipeIngredientsRequest;
use App\Http\Requests\StoreRecipeRequest;
use App\Models\FoodDiaryEntry;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Services\FatSecretService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
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
        $recipes = Recipe::query()
            ->where(fn ($query) => $query
                ->where('user_id', $request->user()->id)
                ->orWhere('is_public', true))
            ->with('user:id,name')
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
                function (array $food): array {
                    $serving = (string) ($food['serving_description'] ?? '');
                    $base = $this->servingBase($serving);

                    return [
                        'food_id' => (string) ($food['food_id'] ?? ''),
                        'food_name' => (string) ($food['food_name'] ?? ''),
                        'serving_description' => $serving,
                        'base_quantity' => $base['quantity'],
                        'unit' => $base['unit'],
                        'calories' => (float) ($food['calories'] ?? 0),
                        'protein' => (float) ($food['protein'] ?? 0),
                        'carbs' => (float) ($food['carbs'] ?? 0),
                        'fat' => (float) ($food['fat'] ?? 0),
                    ];
                },
                $search->data()['results'],
            )),
            'error' => $this->errors->ingredient($search->error()),
        ]);
    }

    public function store(StoreRecipeRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $ingredients = array_map(fn (array $ing): array => [
            'food_id' => $ing['food_id'],
            'food_name' => $ing['food_name'],
            'quantity' => $ing['quantity'],
            'unit' => $ing['unit'] ?? 'g',
            'calories' => (int) round((float) ($ing['calories'] ?? 0)),
            'protein' => round((float) ($ing['protein'] ?? 0), 2),
            'carbs' => round((float) ($ing['carbs'] ?? 0), 2),
            'fat' => round((float) ($ing['fat'] ?? 0), 2),
        ], $validated['ingredients']);

        $totals = [
            'calories' => array_sum(array_column($ingredients, 'calories')),
            'protein' => array_sum(array_column($ingredients, 'protein')),
            'carbs' => array_sum(array_column($ingredients, 'carbs')),
            'fat' => array_sum(array_column($ingredients, 'fat')),
        ];

        DB::transaction(function () use ($request, $validated, $ingredients, $totals): void {
            $recipe = Recipe::create([
                'user_id' => $request->user()->id,
                'name' => $validated['name'],
                'servings' => $validated['servings'],
                'prep_time' => $validated['prep_time'],
                'is_public' => (bool) ($validated['is_public'] ?? false),
                'tags' => $validated['tags'] ?? [],
                'instructions' => $this->normalizeInstructions($validated['instructions'] ?? []),
                'total_calories' => $totals['calories'],
                'total_protein' => $totals['protein'],
                'total_carbs' => $totals['carbs'],
                'total_fat' => $totals['fat'],
            ]);

            $rows = array_map(fn (array $ing): array => [
                'recipe_id' => $recipe->id,
                'food_id' => $ing['food_id'],
                'food_name' => $ing['food_name'],
                'quantity' => $ing['quantity'],
                'unit' => $ing['unit'] ?? 'g',
                'calories' => $ing['calories'] ?? 0,
                'protein' => $ing['protein'] ?? 0,
                'carbs' => $ing['carbs'] ?? 0,
                'fat' => $ing['fat'] ?? 0,
            ], $ingredients);

            RecipeIngredient::insert($rows);
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

    public function addToJournal(Request $request, Recipe $recipe): RedirectResponse
    {
        Gate::authorize('view', $recipe);

        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'meal_type' => ['required', Rule::in(['breakfast', 'lunch', 'snack', 'dinner'])],
            'servings' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
        ]);

        $servings = (float) ($validated['servings'] ?? 1);
        $baseServings = max($recipe->servings, 1);

        FoodDiaryEntry::create([
            'user_id' => $request->user()->id,
            'date' => $validated['date'] ?? now()->toDateString(),
            'meal_type' => $validated['meal_type'],
            'food_id' => 'recipe:'.$recipe->id,
            'food_name' => $recipe->name,
            'serving_description' => $servings.' portion'.($servings > 1 ? 's' : '').' de recette',
            'quantity' => $servings,
            'calories' => (int) round(($recipe->total_calories / $baseServings) * $servings),
            'protein' => round(((float) $recipe->total_protein / $baseServings) * $servings, 1),
            'carbs' => round(((float) $recipe->total_carbs / $baseServings) * $servings, 1),
            'fat' => round(((float) $recipe->total_fat / $baseServings) * $servings, 1),
            'fiber' => 0,
        ]);

        return back();
    }

    private function normalizeInstructions(array $instructions): array
    {
        return array_values(array_filter(array_map(
            static fn (mixed $instruction): string => trim((string) $instruction),
            $instructions,
        )));
    }

    /**
     * @return array{quantity: float, unit: string}
     */
    private function servingBase(string $description): array
    {
        if (preg_match('/(\d+(?:[,.]\d+)?)\s*(g|grammes?|grams?|ml|millilitres?)/i', $description, $matches)) {
            $unit = str_starts_with(strtolower($matches[2]), 'm') ? 'ml' : 'g';

            return [
                'quantity' => (float) str_replace(',', '.', $matches[1]),
                'unit' => $unit,
            ];
        }

        return ['quantity' => 1.0, 'unit' => 'portion'];
    }
}
