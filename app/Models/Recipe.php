<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    protected $fillable = [
        'user_id', 'name', 'servings', 'prep_time', 'category',
        'tags', 'instructions', 'image_path', 'is_public', 'total_calories', 'total_protein',
        'total_carbs', 'total_fat',
    ];

    protected $appends = ['image_url'];

    protected $casts = [
        'tags' => 'array',
        'instructions' => 'array',
        'is_public' => 'boolean',
        'servings' => 'integer',
        'prep_time' => 'integer',
        'total_calories' => 'integer',
        'total_protein' => 'decimal:2',
        'total_carbs' => 'decimal:2',
        'total_fat' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function caloriesPerServing(): int
    {
        return $this->servings > 0
            ? (int) round($this->total_calories / $this->servings)
            : $this->total_calories;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? '/storage/'.ltrim($this->image_path, '/')
            : null;
    }
}
