<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'rank',
        'points',
        'awarded_week',
        'metadata',
        'awarded_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'metadata' => 'array',
        'awarded_at' => 'datetime',
    ];
}
