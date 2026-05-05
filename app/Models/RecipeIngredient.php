<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeIngredient extends Model
{
    protected $fillable = [
        'recipe_id', 'food_id', 'food_name', 'quantity',
        'unit', 'calories', 'protein', 'carbs', 'fat',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'protein'  => 'decimal:2',
        'carbs'    => 'decimal:2',
        'fat'      => 'decimal:2',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
