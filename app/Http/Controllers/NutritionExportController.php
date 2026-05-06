<?php

namespace App\Http\Controllers;

use App\Models\FoodDiaryEntry;
use App\Models\Recipe;
use App\Models\UserGoal;
use App\Models\WaterTracking;
use App\Models\WeightEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NutritionExportController extends Controller
{
    public function json(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        return response()->json([
            'exported_at' => now()->toISOString(),
            'goals' => UserGoal::where('user_id', $userId)->first(),
            'diary_entries' => FoodDiaryEntry::where('user_id', $userId)->orderBy('date')->get(),
            'water_tracking' => WaterTracking::where('user_id', $userId)->orderBy('date')->get(),
            'weight_entries' => WeightEntry::where('user_id', $userId)->orderBy('date')->get(),
            'recipes' => Recipe::where('user_id', $userId)->with('ingredients')->orderBy('name')->get(),
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $userId = $request->user()->id;
        $filename = 'luckyfood-export-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($userId): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'type', 'date', 'meal_type', 'name', 'quantity', 'calories',
                'protein', 'carbs', 'fat', 'fiber', 'water_glasses', 'weight',
            ]);

            FoodDiaryEntry::where('user_id', $userId)
                ->orderBy('date')
                ->orderBy('meal_type')
                ->each(function (FoodDiaryEntry $entry) use ($handle): void {
                    fputcsv($handle, [
                        'food',
                        $entry->date,
                        $entry->meal_type,
                        $entry->food_name,
                        $entry->quantity,
                        $entry->calories,
                        $entry->protein,
                        $entry->carbs,
                        $entry->fat,
                        $entry->fiber,
                        '',
                        '',
                    ]);
                });

            WaterTracking::where('user_id', $userId)
                ->orderBy('date')
                ->each(fn (WaterTracking $water) => fputcsv($handle, [
                    'water', $water->date, '', '', '', '', '', '', '', '', $water->glasses, '',
                ]));

            WeightEntry::where('user_id', $userId)
                ->orderBy('date')
                ->each(fn (WeightEntry $weight) => fputcsv($handle, [
                    'weight', $weight->date->format('Y-m-d'), '', '', '', '', '', '', '', '', '', $weight->weight,
                ]));

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
