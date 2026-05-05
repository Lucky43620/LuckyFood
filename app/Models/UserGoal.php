<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGoal extends Model
{
    protected $fillable = [
        'user_id', 'calories_goal', 'protein_goal', 'carbs_goal',
        'fat_goal', 'fiber_goal', 'water_goal', 'weight_current',
        'weight_goal', 'activity_level', 'goal_type', 'gender',
    ];

    protected $casts = [
        'weight_current' => 'decimal:1',
        'weight_goal'    => 'decimal:1',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
