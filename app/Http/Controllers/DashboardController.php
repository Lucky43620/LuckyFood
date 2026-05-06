<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWaterRequest;
use App\Models\FoodDiaryEntry;
use App\Models\UserGoal;
use App\Models\WaterTracking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $today = now()->toDateString();

        $goal = UserGoal::firstOrCreate(
            ['user_id' => $user->id],
            ['calories_goal' => 2000, 'protein_goal' => 150, 'carbs_goal' => 250, 'fat_goal' => 65, 'fiber_goal' => 30, 'water_goal' => 8]
        );

        $entries = FoodDiaryEntry::where('user_id', $user->id)
            ->where('date', $today)
            ->get();

        $water = WaterTracking::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['glasses' => 0]
        );

        $totals = [
            'calories' => $entries->sum('calories'),
            'protein' => round($entries->sum('protein'), 1),
            'carbs' => round($entries->sum('carbs'), 1),
            'fat' => round($entries->sum('fat'), 1),
            'fiber' => round($entries->sum('fiber'), 1),
        ];

        return Inertia::render('Dashboard', [
            'goal' => $goal,
            'totals' => $totals,
            'meals' => $entries->groupBy('meal_type'),
            'water' => $water->glasses,
            'date' => $today,
        ]);
    }

    public function updateWater(UpdateWaterRequest $request): void
    {
        $validated = $request->validated();
        $today = now()->toDateString();

        WaterTracking::updateOrCreate(
            ['user_id' => $request->user()->id, 'date' => $today],
            ['glasses' => $validated['water']]
        );
    }
}
