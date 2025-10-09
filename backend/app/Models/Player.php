<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'alliance_id',
        'legacy_id',
        'name',
        'tribe',
        'gender',
        'population',
        'culture_points',
        'villages_count',
        'capital_village_id',
        'beginners_protection_ends_at',
        'vacation_until',
        'is_hidden',
        'ui_preferences',
    ];

    protected $casts = [
        'beginners_protection_ends_at' => 'datetime',
        'vacation_until' => 'datetime',
        'is_hidden' => 'boolean',
        'ui_preferences' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function allianceMemberships(): HasMany
    {
        return $this->hasMany(AllianceMember::class);
    }

    public function alliances(): BelongsToMany
    {
        return $this->belongsToMany(Alliance::class, 'alliance_members')
            ->withPivot(['role', 'is_leader', 'is_founder', 'contribution_points', 'joined_at'])
            ->withTimestamps();
    }

    public function capital(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'capital_village_id');
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }

    public function hero(): HasOne
    {
        return $this->hasOne(HeroState::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'recipient_player_id');
    }

    public function sentReports(): HasMany
    {
        return $this->hasMany(Report::class, 'sender_player_id');
    }

    public function quests(): HasMany
    {
        return $this->hasMany(QuestProgress::class);
    }

    public function dailyQuests(): HasMany
    {
        return $this->hasMany(DailyQuestProgress::class);
    }

    public function farmLists(): HasMany
    {
        return $this->hasMany(FarmList::class);
    }
}
