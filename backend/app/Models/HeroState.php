<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HeroState extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'home_village_id',
        'current_village_id',
        'experience',
        'level',
        'health',
        'is_dead',
        'adventure_points',
        'attributes',
    ];

    protected $casts = [
        'experience' => 'integer',
        'level' => 'integer',
        'health' => 'decimal:2',
        'is_dead' => 'boolean',
        'adventure_points' => 'integer',
        'attributes' => 'array',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function homeVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'home_village_id');
    }

    public function currentVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'current_village_id');
    }

    public function adventures(): HasMany
    {
        return $this->hasMany(HeroAdventure::class);
    }
}
