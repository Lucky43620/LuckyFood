<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterTracking extends Model
{
    protected $table = 'water_tracking';

    protected $fillable = ['user_id', 'date', 'glasses'];

    protected $casts = [
        'glasses' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
