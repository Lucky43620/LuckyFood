<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FoodDiaryController;
use App\Http\Controllers\FoodSearchController;
use App\Http\Controllers\ProgressionController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\UserGoalController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'    => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {

        // ── Tableau de bord ─────────────────────────────────────────────────────
        Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard');
        Route::patch('/tableau-de-bord', [DashboardController::class, 'updateWater']);

        // ── Journal alimentaire ─────────────────────────────────────────────────
        Route::get('/journal', [FoodDiaryController::class, 'index'])->name('journal.index');
        Route::post('/journal', [FoodDiaryController::class, 'store'])->name('journal.store');
        Route::delete('/journal/{entry}', [FoodDiaryController::class, 'destroy'])->name('journal.destroy');

        // ── Recherche ───────────────────────────────────────────────────────────
        Route::get('/recherche', [FoodSearchController::class, 'index'])->name('search.index');
        Route::get('/recherche/autocomplete', [FoodSearchController::class, 'autocomplete'])->name('search.autocomplete');
        Route::get('/recherche/aliment/{foodId}', [FoodSearchController::class, 'show'])->name('search.show');

        // ── Recettes ────────────────────────────────────────────────────────────
        Route::resource('recettes', RecipeController::class)
            ->names('recipes')
            ->parameters(['recettes' => 'recipe'])
            ->except(['edit', 'update']);

        // ── Progression ─────────────────────────────────────────────────────────
        Route::get('/progression', [ProgressionController::class, 'index'])->name('progression');

        // ── Objectifs ───────────────────────────────────────────────────────────
        Route::get('/objectifs', [UserGoalController::class, 'index'])->name('goals.index');
        Route::put('/objectifs', [UserGoalController::class, 'update'])->name('goals.update');
    });
