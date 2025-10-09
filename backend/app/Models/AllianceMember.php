<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllianceMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'alliance_id',
        'player_id',
        'role',
        'is_leader',
        'is_founder',
        'contribution_points',
        'joined_at',
    ];

    protected $casts = [
        'is_leader' => 'boolean',
        'is_founder' => 'boolean',
        'contribution_points' => 'integer',
        'joined_at' => 'datetime',
    ];

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
