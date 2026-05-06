<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserGoalRequest;
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
                'calories_goal' => 2000,
                'protein_goal' => 150,
                'carbs_goal' => 250,
                'fat_goal' => 65,
                'fiber_goal' => 30,
                'water_goal' => 8,
                'activity_level' => 'moderate',
                'goal_type' => 'maintain',
            ]
        );

        return Inertia::render('Objectifs', [
            'goal' => $goal,
        ]);
    }

    public function update(UpdateUserGoalRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        UserGoal::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return back();
    }
}
