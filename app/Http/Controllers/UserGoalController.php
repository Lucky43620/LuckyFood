<?php

namespace App\Http\Controllers;

use App\Models\UserGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserGoalController extends Controller
{
    public function index(Request $request): Response
    {
        $goal = UserGoal::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'calories_goal'  => 2000,
                'protein_goal'   => 150,
                'carbs_goal'     => 250,
                'fat_goal'       => 65,
                'fiber_goal'     => 30,
                'water_goal'     => 8,
                'activity_level' => 'moderate',
                'goal_type'      => 'maintain',
            ]
        );

        return Inertia::render('Objectifs', [
            'goal' => $goal,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'calories_goal'  => 'required|integer|min:500|max:10000',
            'protein_goal'   => 'required|integer|min:0|max:500',
            'carbs_goal'     => 'required|integer|min:0|max:1000',
            'fat_goal'       => 'required|integer|min:0|max:300',
            'fiber_goal'     => 'nullable|integer|min:0|max:200',
            'water_goal'     => 'nullable|integer|min:0|max:30',
            'weight_current' => 'nullable|numeric|min:20|max:500',
            'weight_goal'    => 'nullable|numeric|min:20|max:500',
            'activity_level' => 'required|in:sedentary,light,moderate,active,very_active',
            'goal_type'      => 'required|in:lose,maintain,gain',
            'gender'         => 'nullable|in:male,female,other',
        ]);

        UserGoal::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return back();
    }
}
