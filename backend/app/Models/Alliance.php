<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Alliance extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tag',
        'public_description',
        'internal_description',
        'news',
        'member_limit',
        'leader_id',
        'total_attack_points',
        'total_defense_points',
        'weekly_attack_points',
        'weekly_defense_points',
        'weekly_robber_points',
        'weekly_population_change',
    ];

    protected $casts = [
        'member_limit' => 'integer',
        'total_attack_points' => 'integer',
        'total_defense_points' => 'integer',
        'weekly_attack_points' => 'integer',
        'weekly_defense_points' => 'integer',
        'weekly_robber_points' => 'integer',
        'weekly_population_change' => 'integer',
    ];

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'leader_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'alliance_members')
            ->withPivot(['role', 'is_leader', 'is_founder', 'contribution_points', 'joined_at'])
            ->withTimestamps();
    }

    public function memberRecords(): HasMany
    {
        return $this->hasMany(AllianceMember::class);
    }

    public function villages(): HasManyThrough
    {
        return $this->hasManyThrough(Village::class, Player::class);
    }
}
