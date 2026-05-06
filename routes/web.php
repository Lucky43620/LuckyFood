<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoriteFoodController;
use App\Http\Controllers\FoodDiaryController;
use App\Http\Controllers\FoodSearchController;
use App\Http\Controllers\NutritionExportController;
use App\Http\Controllers\ProgressionController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\UserGoalController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome', [
    'canLogin' => Route::has('login'),
    'canRegister' => Route::has('register'),
]))->name('home');

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {

        // ── Tableau de bord ─────────────────────────────────────────────────────
        Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard');
        Route::patch('/tableau-de-bord', [DashboardController::class, 'updateWater']);

        // ── Journal alimentaire ─────────────────────────────────────────────────
        Route::get('/journal', [FoodDiaryController::class, 'index'])->name('journal.index');
        Route::post('/journal', [FoodDiaryController::class, 'store'])->name('journal.store');
        Route::post('/journal/repeter-hier', [FoodDiaryController::class, 'repeatYesterday'])->name('journal.repeat-yesterday');
        Route::put('/journal/{entry}', [FoodDiaryController::class, 'update'])->name('journal.update');
        Route::delete('/journal/{entry}', [FoodDiaryController::class, 'destroy'])->name('journal.destroy');

        // ── Recherche ───────────────────────────────────────────────────────────
        Route::get('/recherche', [FoodSearchController::class, 'index'])->name('search.index');
        Route::get('/recherche/autocomplete', [FoodSearchController::class, 'autocomplete'])
            ->middleware('throttle:fatsecret')
            ->name('search.autocomplete');
        Route::get('/recherche/code-barre', [FoodSearchController::class, 'barcode'])
            ->middleware('throttle:fatsecret')
            ->name('search.barcode');
        Route::get('/recherche/aliment/{foodId}', [FoodSearchController::class, 'show'])->name('search.show');
        Route::post('/favoris/aliments', [FavoriteFoodController::class, 'store'])->name('favorite-foods.store');
        Route::delete('/favoris/aliments/{foodId}', [FavoriteFoodController::class, 'destroy'])->name('favorite-foods.destroy');

        // ── Recettes ────────────────────────────────────────────────────────────
        Route::get('/recettes/ingredients/search', [RecipeController::class, 'searchIngredients'])
            ->middleware('throttle:fatsecret')
            ->name('recipes.ingredients.search');
        Route::resource('recettes', RecipeController::class)
            ->names('recipes')
            ->parameters(['recettes' => 'recipe'])
            ->except(['edit', 'update']);
        Route::post('/recettes/{recipe}/journal', [RecipeController::class, 'addToJournal'])->name('recipes.add-to-journal');

        // ── Progression ─────────────────────────────────────────────────────────
        Route::get('/progression', [ProgressionController::class, 'index'])->name('progression');
        Route::put('/progression/poids', [ProgressionController::class, 'updateWeight'])->name('progression.update-weight');

        // ── Objectifs ───────────────────────────────────────────────────────────
        Route::get('/objectifs', [UserGoalController::class, 'index'])->name('goals.index');
        Route::put('/objectifs', [UserGoalController::class, 'update'])->name('goals.update');

        // ── Exports ─────────────────────────────────────────────────────────────
        Route::get('/export/nutrition.csv', [NutritionExportController::class, 'csv'])->name('nutrition.export.csv');
        Route::get('/export/nutrition.json', [NutritionExportController::class, 'json'])->name('nutrition.export.json');
    });
