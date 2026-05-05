<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodDiaryEntry extends Model
{
    protected $fillable = [
        'user_id', 'date', 'meal_type', 'food_id', 'food_name',
        'serving_description', 'quantity', 'calories',
        'protein', 'carbs', 'fat', 'fiber',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'protein'  => 'decimal:2',
        'carbs'    => 'decimal:2',
        'fat'      => 'decimal:2',
        'fiber'    => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
