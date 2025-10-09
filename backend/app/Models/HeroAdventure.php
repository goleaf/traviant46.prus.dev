<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroAdventure extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'hero_state_id',
        'target_village_id',
        'target_x',
        'target_y',
        'difficulty',
        'reward_type',
        'reward_payload',
        'status',
        'available_at',
        'expires_at',
        'completed_at',
    ];

    protected $casts = [
        'target_x' => 'integer',
        'target_y' => 'integer',
        'reward_payload' => 'array',
        'available_at' => 'datetime',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function heroState(): BelongsTo
    {
        return $this->belongsTo(HeroState::class);
    }

    public function targetVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'target_village_id');
    }
}
