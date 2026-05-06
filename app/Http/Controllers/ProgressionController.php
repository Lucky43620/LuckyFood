<?php

namespace App\Http\Controllers;

use App\Models\FoodDiaryEntry;
use App\Models\UserGoal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgressionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $goal = UserGoal::where('user_id', $user->id)->first();

        $dayLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $startDate = now()->subDays(6)->toDateString();
        $endDate = now()->toDateString();
        $caloriesByDate = FoodDiaryEntry::query()
            ->selectRaw('date, SUM(calories) as calories')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('date')
            ->pluck('calories', 'date');

        $weeklyData = collect(range(6, 0))->map(function ($daysAgo) use ($caloriesByDate, $dayLabels) {
            $date = now()->subDays($daysAgo)->toDateString();
            $dow = (int) now()->subDays($daysAgo)->format('N') - 1;

            return [
                'date' => $date,
                'day' => $dayLabels[$dow],
                'calories' => (int) ($caloriesByDate[$date] ?? 0),
                'weight' => null,
            ];
        })->values()->all();

        $allCalories = collect($weeklyData)->pluck('calories')->filter()->all();
        $avgCalories = count($allCalories) ? (int) round(array_sum($allCalories) / count($allCalories)) : 0;

        $stats = [
            'avgCalories' => $avgCalories,
            'currentWeight' => $goal?->weight_current,
            'totalLoss' => $goal && $goal->weight_current && $goal->weight_goal
                ? round($goal->weight_current - $goal->weight_goal, 1)
                : null,
            'goalCalories' => $goal?->calories_goal ?? 2000,
        ];

        return Inertia::render('Progression', [
            'weeklyData' => $weeklyData,
            'stats' => $stats,
        ]);
    }
}
