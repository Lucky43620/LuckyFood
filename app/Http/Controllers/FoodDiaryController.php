<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFoodDiaryEntryRequest;
use App\Models\FoodDiaryEntry;
use App\Models\UserGoal;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
            'protein' => round($entries->sum('protein'), 1),
            'carbs' => round($entries->sum('carbs'), 1),
            'fat' => round($entries->sum('fat'), 1),
            'fiber' => round($entries->sum('fiber'), 1),
        ];

        return Inertia::render('Journal', [
            'date' => $date,
            'entries' => $entries,
            'totals' => $totals,
            'goal' => $goal,
        ]);
    }

    public function store(StoreFoodDiaryEntryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        FoodDiaryEntry::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'date' => $request->entryDate(),
        ]);

        return back();
    }

    public function update(StoreFoodDiaryEntryRequest $request, FoodDiaryEntry $entry): RedirectResponse
    {
        Gate::authorize('update', $entry);

        $validated = $request->validated();

        $entry->update([
            ...$validated,
            'date' => $request->entryDate(),
        ]);

        return back();
    }

    public function repeatYesterday(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $targetDate = (string) ($validated['date'] ?? now()->toDateString());
        $sourceDate = Carbon::createFromFormat('Y-m-d', $targetDate)->subDay()->toDateString();
        $userId = $request->user()->id;

        FoodDiaryEntry::where('user_id', $userId)
            ->where('date', $sourceDate)
            ->orderBy('created_at')
            ->get()
            ->each(function (FoodDiaryEntry $entry) use ($targetDate): void {
                FoodDiaryEntry::create([
                    ...$entry->only([
                        'user_id', 'meal_type', 'food_id', 'food_name', 'serving_description',
                        'quantity', 'calories', 'protein', 'carbs', 'fat', 'fiber',
                    ]),
                    'date' => $targetDate,
                ]);
            });

        return back();
    }

    public function destroy(FoodDiaryEntry $entry): RedirectResponse
    {
        Gate::authorize('delete', $entry);
        $entry->delete();

        return back();
    }
}
