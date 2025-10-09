<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TroopMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'origin_village_id',
        'target_village_id',
        'player_id',
        'movement_type',
        'units',
        'hero_included',
        'started_at',
        'arrives_at',
        'returns_at',
    ];

    protected $casts = [
        'units' => 'array',
        'hero_included' => 'boolean',
        'started_at' => 'datetime',
        'arrives_at' => 'datetime',
        'returns_at' => 'datetime',
    ];

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'origin_village_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'target_village_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
