<?php

namespace App\Http\Controllers;

use App\Models\FoodDiaryEntry;
use App\Models\UserGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FoodDiaryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $date = $request->input('date', now()->toDateString());

        $goal = UserGoal::firstOrCreate(
            ['user_id' => $user->id],
            ['calories_goal' => 2000, 'protein_goal' => 150, 'carbs_goal' => 250, 'fat_goal' => 65]
        );

        $entries = FoodDiaryEntry::where('user_id', $user->id)
            ->where('date', $date)
            ->orderBy('created_at')
            ->get();

        $totals = [
            'calories' => $entries->sum('calories'),
            'protein'  => round($entries->sum('protein'), 1),
            'carbs'    => round($entries->sum('carbs'), 1),
            'fat'      => round($entries->sum('fat'), 1),
            'fiber'    => round($entries->sum('fiber'), 1),
        ];

        return Inertia::render('Journal', [
            'date'    => $date,
            'entries' => $entries,
            'totals'  => $totals,
            'goal'    => $goal,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'food_id'             => 'required|string|max:255',
            'food_name'           => 'required|string|max:255',
            'meal_type'           => 'required|in:breakfast,lunch,snack,dinner',
            'calories'            => 'required|numeric|min:0',
            'protein'             => 'nullable|numeric|min:0',
            'carbs'               => 'nullable|numeric|min:0',
            'fat'                 => 'nullable|numeric|min:0',
            'fiber'               => 'nullable|numeric|min:0',
            'serving_description' => 'nullable|string|max:255',
            'quantity'            => 'nullable|numeric|min:0',
        ]);

        FoodDiaryEntry::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'date'    => now()->toDateString(),
        ]);

        return back();
    }

    public function destroy(FoodDiaryEntry $entry): RedirectResponse
    {
        abort_unless($entry->user_id === request()->user()->id, 403);
        $entry->delete();

        return back();
    }
}
