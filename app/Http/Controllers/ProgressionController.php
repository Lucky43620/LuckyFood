<?php

namespace App\Http\Controllers;

use App\Models\FoodDiaryEntry;
use App\Models\UserGoal;
use App\Models\WaterTracking;
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

        // Last 7 days of diary data
        $weeklyData = collect(range(6, 0))->map(function ($daysAgo) use ($user, $dayLabels) {
            $date    = now()->subDays($daysAgo)->toDateString();
            $dow     = (int) now()->subDays($daysAgo)->format('N') - 1; // 0=Mon … 6=Sun
            $entries = FoodDiaryEntry::where('user_id', $user->id)->where('date', $date)->get();

            return [
                'date'     => $date,
                'day'      => $dayLabels[$dow],
                'calories' => $entries->sum('calories'),
                'weight'   => null,
            ];
        })->values()->all();

        $allCalories = collect($weeklyData)->pluck('calories')->filter()->all();
        $avgCalories = count($allCalories) ? (int) round(array_sum($allCalories) / count($allCalories)) : 0;

        $stats = [
            'avgCalories'   => $avgCalories,
            'currentWeight' => $goal?->weight_current,
            'totalLoss'     => $goal && $goal->weight_current && $goal->weight_goal
                ? round($goal->weight_current - $goal->weight_goal, 1)
                : null,
            'goalCalories'  => $goal?->calories_goal ?? 2000,
        ];

        return Inertia::render('Progression', [
            'weeklyData' => $weeklyData,
            'stats'      => $stats,
        ]);
    }
}
