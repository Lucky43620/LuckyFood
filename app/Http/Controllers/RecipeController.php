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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $ingredients = $this->ingredientRows($validated['ingredients']);
        $totals = $this->totalsFromIngredients($ingredients);
        $imagePath = $this->storeImage($request);

        DB::transaction(function () use ($request, $validated, $ingredients, $totals, $imagePath): void {
            $recipe = Recipe::create([
                'user_id' => $request->user()->id,
                'name' => $validated['name'],
                'servings' => $validated['servings'],
                'prep_time' => $validated['prep_time'],
                'is_public' => (bool) ($validated['is_public'] ?? false),
                'tags' => $validated['tags'] ?? [],
                'instructions' => $this->normalizeInstructions($validated['instructions'] ?? []),
                'image_path' => $imagePath,
                'total_calories' => $totals['calories'],
                'total_protein' => $totals['protein'],
                'total_carbs' => $totals['carbs'],
                'total_fat' => $totals['fat'],
            ]);

            $this->insertIngredients($recipe, $ingredients);
        });

        return redirect()->route('recipes.index');
    }

    public function edit(Recipe $recipe): Response
    {
        Gate::authorize('update', $recipe);

        return Inertia::render('Recettes/Create', [
            'recipe' => $recipe->load('ingredients'),
            'isEditing' => true,
        ]);
    }

    public function update(StoreRecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        Gate::authorize('update', $recipe);

        $validated = $request->validated();
        $ingredients = $this->ingredientRows($validated['ingredients']);
        $totals = $this->totalsFromIngredients($ingredients);
        $oldImagePath = $recipe->image_path;
        $newImagePath = $this->storeImage($request);

        DB::transaction(function () use ($recipe, $validated, $ingredients, $totals, $oldImagePath, $newImagePath): void {
            $recipe->update([
                'name' => $validated['name'],
                'servings' => $validated['servings'],
                'prep_time' => $validated['prep_time'],
                'is_public' => (bool) ($validated['is_public'] ?? false),
                'tags' => $validated['tags'] ?? [],
                'instructions' => $this->normalizeInstructions($validated['instructions'] ?? []),
                'image_path' => $newImagePath ?? $oldImagePath,
                'total_calories' => $totals['calories'],
                'total_protein' => $totals['protein'],
                'total_carbs' => $totals['carbs'],
                'total_fat' => $totals['fat'],
            ]);

            $recipe->ingredients()->delete();
            $this->insertIngredients($recipe, $ingredients);
        });

        if ($newImagePath !== null && $oldImagePath !== null) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return redirect()->route('recipes.show', $recipe);
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
        $imagePath = $recipe->image_path;

        $recipe->delete();

        if ($imagePath !== null) {
            Storage::disk('public')->delete($imagePath);
        }

        return back();
    }

    public function duplicate(Request $request, Recipe $recipe): RedirectResponse
    {
        Gate::authorize('view', $recipe);

        $recipe->loadMissing('ingredients');
        $imagePath = $this->duplicateImage($recipe->image_path);

        $copy = DB::transaction(function () use ($request, $recipe, $imagePath): Recipe {
            $copy = Recipe::create([
                'user_id' => $request->user()->id,
                'name' => $recipe->name.' (copie)',
                'servings' => $recipe->servings,
                'prep_time' => $recipe->prep_time,
                'category' => $recipe->category,
                'tags' => $recipe->tags ?? [],
                'instructions' => $recipe->instructions ?? [],
                'image_path' => $imagePath,
                'is_public' => false,
                'total_calories' => $recipe->total_calories,
                'total_protein' => $recipe->total_protein,
                'total_carbs' => $recipe->total_carbs,
                'total_fat' => $recipe->total_fat,
            ]);

            $ingredients = $recipe->ingredients
                ->map(fn (RecipeIngredient $ingredient): array => [
                    'food_id' => $ingredient->food_id,
                    'food_name' => $ingredient->food_name,
                    'quantity' => $ingredient->quantity,
                    'unit' => $ingredient->unit,
                    'calories' => $ingredient->calories,
                    'protein' => $ingredient->protein,
                    'carbs' => $ingredient->carbs,
                    'fat' => $ingredient->fat,
                ])
                ->all();

            $this->insertIngredients($copy, $ingredients);

            return $copy;
        });

        return redirect()->route('recipes.edit', $copy);
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
     * @param  array<int, array<string, mixed>>  $ingredients
     * @return array<int, array<string, mixed>>
     */
    private function ingredientRows(array $ingredients): array
    {
        return array_map(fn (array $ing): array => [
            'food_id' => $ing['food_id'],
            'food_name' => $ing['food_name'],
            'quantity' => $ing['quantity'],
            'unit' => $ing['unit'] ?? 'g',
            'calories' => (int) round((float) ($ing['calories'] ?? 0)),
            'protein' => round((float) ($ing['protein'] ?? 0), 2),
            'carbs' => round((float) ($ing['carbs'] ?? 0), 2),
            'fat' => round((float) ($ing['fat'] ?? 0), 2),
        ], $ingredients);
    }

    /**
     * @param  array<int, array<string, mixed>>  $ingredients
     * @return array{calories: int|float, protein: int|float, carbs: int|float, fat: int|float}
     */
    private function totalsFromIngredients(array $ingredients): array
    {
        return [
            'calories' => array_sum(array_column($ingredients, 'calories')),
            'protein' => array_sum(array_column($ingredients, 'protein')),
            'carbs' => array_sum(array_column($ingredients, 'carbs')),
            'fat' => array_sum(array_column($ingredients, 'fat')),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $ingredients
     */
    private function insertIngredients(Recipe $recipe, array $ingredients): void
    {
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

        if ($rows === []) {
            return;
        }

        RecipeIngredient::insert($rows);
    }

    private function storeImage(StoreRecipeRequest $request): ?string
    {
        $file = $request->file('image');

        if (! $file instanceof UploadedFile) {
            return null;
        }

        $path = $file->store('recipes', 'public');

        return $path === false ? null : $path;
    }

    private function duplicateImage(?string $imagePath): ?string
    {
        if ($imagePath === null || ! Storage::disk('public')->exists($imagePath)) {
            return null;
        }

        $extension = pathinfo($imagePath, PATHINFO_EXTENSION) ?: 'jpg';
        $copyPath = 'recipes/'.Str::uuid().'.'.$extension;

        Storage::disk('public')->copy($imagePath, $copyPath);

        return $copyPath;
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
