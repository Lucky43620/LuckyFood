<?php

namespace App\Http\Controllers;

use App\Models\FoodDiaryEntry;
use App\Models\UserGoal;
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

        $weeklyData = collect(range(0, 6))->map(function ($offset) use ($startOfWeek, $caloriesByDate, $dayLabels, $today) {
            $date = $startOfWeek->copy()->addDays($offset)->toDateString();

            return [
                'date' => $date,
                'day' => $dayLabels[$offset],
                'calories' => (int) ($caloriesByDate[$date] ?? 0),
                'isFuture' => $date > $today,
                'isToday' => $date === $today,
                'weight' => null,
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

        $monthlyData = collect(range(3, 0))->map(function ($weeksAgo) use ($monthlyCaloriesByDate, $monthNames, $today) {
            $weekStart = now()->startOfWeek(Carbon::MONDAY)->subWeeks($weeksAgo)->copy();
            $weekEnd = $weekStart->copy()->addDays(6);

            $weekCals = collect(range(0, 6))
                ->map(fn ($d) => $weekStart->copy()->addDays($d)->toDateString())
                ->filter(fn ($date) => $date <= $today)
                ->map(fn ($date) => (int) ($monthlyCaloriesByDate[$date] ?? 0))
                ->filter(fn ($cal) => $cal > 0);

            $label = $weekStart->day.' '.$monthNames[$weekStart->month - 1];

            return [
                'label' => $label,
                'weekStart' => $weekStart->toDateString(),
                'weekEnd' => $weekEnd->toDateString(),
                'totalCalories' => (int) $weekCals->sum(),
                'avgCalories' => $weekCals->count() > 0 ? (int) round($weekCals->sum() / $weekCals->count()) : 0,
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
        $avgCalories = count($allCalories) ? (int) round(array_sum($allCalories) / count($allCalories)) : 0;

        $stats = [
            'avgCalories' => $avgCalories,
            'currentWeight' => $goal?->weight_current,
            'totalLoss' => $goal && $goal->weight_current && $goal->weight_goal
                ? round($goal->weight_current - $goal->weight_goal, 1)
                : null,
            'goalCalories' => $goal?->calories_goal ?? 2000,
            'streak' => $streak,
        ];

        return Inertia::render('Progression', [
            'weeklyData' => $weeklyData,
            'monthlyData' => $monthlyData,
            'mealBreakdown' => $mealBreakdown,
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

        return back();
    }
}
