<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FavoriteFood extends Model
{
    protected $table = 'favorite_foods';

    protected $fillable = [
        'user_id', 'food_id', 'food_name', 'serving_description',
        'calories', 'protein', 'carbs', 'fat', 'fiber',
    ];

    protected $casts = [
        'calories' => 'integer',
        'protein' => 'decimal:2',
        'carbs' => 'decimal:2',
        'fat' => 'decimal:2',
        'fiber' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
