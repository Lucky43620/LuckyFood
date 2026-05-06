<?php

namespace App\Http\Controllers;

use App\Models\FoodDiaryEntry;
use App\Models\UserGoal;
use App\Models\WaterTracking;
use App\Models\WeightEntry;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
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
        $monthNames = ['jan', 'fév', 'mar', 'avr', 'mai', 'juin', 'juil', 'août', 'sep', 'oct', 'nov', 'déc'];
        $today = now()->toDateString();

        // ── Semaine courante (Lun → Dim) ────────────────────────────────────────
        $startOfWeek = now()->startOfWeek(Carbon::MONDAY)->copy();
        $endOfWeek = $startOfWeek->copy()->addDays(6);

        $caloriesByDate = FoodDiaryEntry::query()
            ->selectRaw('date, SUM(calories) as calories')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->groupBy('date')
            ->pluck('calories', 'date');

        $proteinByDate = FoodDiaryEntry::query()
            ->selectRaw('date, SUM(protein) as protein')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->groupBy('date')
            ->pluck('protein', 'date');

        $weightByDate = WeightEntry::where('user_id', $user->id)
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->pluck('weight', 'date');

        $waterByDate = WaterTracking::where('user_id', $user->id)
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->pluck('glasses', 'date');

        $weeklyData = collect(range(0, 6))->map(function ($offset) use ($startOfWeek, $caloriesByDate, $proteinByDate, $weightByDate, $dayLabels, $today) {
            $date = $startOfWeek->copy()->addDays($offset)->toDateString();

            return [
                'date' => $date,
                'day' => $dayLabels[$offset],
                'calories' => (int) ($caloriesByDate[$date] ?? 0),
                'protein' => round((float) ($proteinByDate[$date] ?? 0), 1),
                'isFuture' => $date > $today,
                'isToday' => $date === $today,
                'weight' => isset($weightByDate[$date]) ? (float) $weightByDate[$date] : null,
            ];
        })->values()->all();

        $waterData = collect(range(0, 6))->map(function ($offset) use ($startOfWeek, $waterByDate, $dayLabels, $today) {
            $date = $startOfWeek->copy()->addDays($offset)->toDateString();

            return [
                'date' => $date,
                'day' => $dayLabels[$offset],
                'glasses' => (int) ($waterByDate[$date] ?? 0),
                'isFuture' => $date > $today,
                'isToday' => $date === $today,
            ];
        })->values()->all();

        // ── 4 dernières semaines ────────────────────────────────────────────────
        $fourWeeksStart = now()->startOfWeek(Carbon::MONDAY)->subWeeks(3)->copy();

        $monthlyCaloriesByDate = FoodDiaryEntry::query()
            ->selectRaw('date, SUM(calories) as calories')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$fourWeeksStart->toDateString(), $today])
            ->groupBy('date')
            ->pluck('calories', 'date');

        $monthlyWeightsByDate = WeightEntry::where('user_id', $user->id)
            ->whereBetween('date', [$fourWeeksStart->toDateString(), $today])
            ->pluck('weight', 'date');

        $monthlyData = collect(range(3, 0))->map(function ($weeksAgo) use ($monthlyCaloriesByDate, $monthlyWeightsByDate, $monthNames, $today) {
            $weekStart = now()->startOfWeek(Carbon::MONDAY)->subWeeks($weeksAgo)->copy();
            $weekEnd = $weekStart->copy()->addDays(6);
            $dates = collect(range(0, 6))
                ->map(fn ($d) => $weekStart->copy()->addDays($d)->toDateString())
                ->filter(fn ($date) => $date <= $today);

            $weekCals = $dates
                ->map(fn ($date) => (int) ($monthlyCaloriesByDate[$date] ?? 0))
                ->filter(fn ($cal) => $cal > 0);
            $weekWeights = $dates
                ->map(fn ($date) => isset($monthlyWeightsByDate[$date]) ? (float) $monthlyWeightsByDate[$date] : null)
                ->filter(fn (?float $weight) => $weight !== null);

            $label = $weekStart->day.' '.$monthNames[$weekStart->month - 1];

            return [
                'label' => $label,
                'weekStart' => $weekStart->toDateString(),
                'weekEnd' => $weekEnd->toDateString(),
                'totalCalories' => (int) $weekCals->sum(),
                'avgCalories' => $weekCals->count() > 0 ? (int) round($weekCals->sum() / $weekCals->count()) : 0,
                'avgWeight' => $weekWeights->count() > 0 ? round($weekWeights->sum() / $weekWeights->count(), 1) : null,
                'daysLogged' => $weekCals->count(),
                'isCurrent' => $weeksAgo === 0,
            ];
        })->values()->all();

        // ── Répartition par repas (semaine courante) ────────────────────────────
        $mealBreakdown = FoodDiaryEntry::query()
            ->selectRaw('meal_type, SUM(calories) as total')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfWeek->toDateString(), $today])
            ->groupBy('meal_type')
            ->pluck('total', 'meal_type')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        // ── Streak : jours consécutifs loggés ──────────────────────────────────
        $distinctDates = FoodDiaryEntry::where('user_id', $user->id)
            ->where('date', '<=', $today)
            ->orderBy('date', 'desc')
            ->distinct()
            ->pluck('date')
            ->toArray();

        $streak = 0;
        $check = Carbon::today();

        // Si aujourd'hui n'a pas encore d'entrée, on commence à compter hier
        if (! in_array($today, $distinctDates) && ! empty($distinctDates)) {
            $check = Carbon::yesterday();
        }

        foreach ($distinctDates as $date) {
            if ($date === $check->toDateString()) {
                $streak++;
                $check->subDay();
            } elseif ($date < $check->toDateString()) {
                break;
            }
        }

        // ── Stats ───────────────────────────────────────────────────────────────
        $allCalories = collect($weeklyData)
            ->filter(fn ($d) => ! $d['isFuture'] && $d['calories'] > 0)
            ->pluck('calories')
            ->all();
        $allProtein = collect($weeklyData)
            ->filter(fn ($d) => ! $d['isFuture'] && $d['calories'] > 0)
            ->pluck('protein')
            ->all();
        $waterValues = collect($waterData)
            ->filter(fn ($d) => ! $d['isFuture'] && $d['glasses'] > 0)
            ->pluck('glasses')
            ->all();
        $avgCalories = count($allCalories) ? (int) round(array_sum($allCalories) / count($allCalories)) : 0;
        $avgProtein = count($allProtein) ? round(array_sum($allProtein) / count($allProtein), 1) : 0;
        $avgWater = count($waterValues) ? round(array_sum($waterValues) / count($waterValues), 1) : 0;
        $latestWeight = WeightEntry::where('user_id', $user->id)
            ->where('date', '<=', $today)
            ->orderByDesc('date')
            ->value('weight');
        $currentWeight = $latestWeight ?? $goal?->weight_current;
        $goalCalories = $goal?->calories_goal ?? 2000;

        $stats = [
            'avgCalories' => $avgCalories,
            'avgProtein' => $avgProtein,
            'avgWater' => $avgWater,
            'daysLogged' => count($allCalories),
            'calorieGap' => $avgCalories > 0 ? $avgCalories - $goalCalories : 0,
            'currentWeight' => $currentWeight,
            'totalLoss' => $currentWeight && $goal?->weight_goal
                ? round((float) $currentWeight - (float) $goal->weight_goal, 1)
                : null,
            'goalCalories' => $goalCalories,
            'goalWater' => $goal?->water_goal ?? 8,
            'streak' => $streak,
        ];

        return Inertia::render('Progression', [
            'weeklyData' => $weeklyData,
            'monthlyData' => $monthlyData,
            'mealBreakdown' => $mealBreakdown,
            'waterData' => $waterData,
            'stats' => $stats,
        ]);
    }

    public function updateWeight(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'weight_current' => ['nullable', 'numeric', 'min:20', 'max:500'],
        ]);

        UserGoal::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['weight_current' => $validated['weight_current']],
        );

        if (($validated['weight_current'] ?? null) !== null) {
            WeightEntry::updateOrCreate(
                ['user_id' => $request->user()->id, 'date' => now()->toDateString()],
                ['weight' => $validated['weight_current']]
            );
        }

        return back();
    }
}
