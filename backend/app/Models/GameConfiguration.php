<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class GameConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'world_started_at',
        'last_daily_quest_reset_at',
        'daily_quest_reset_interval_hours',
        'last_medals_given_at',
        'medal_award_interval_hours',
        'last_alliance_contribution_reset_at',
        'alliance_contribution_reset_interval_hours',
        'artifacts_released_at',
        'world_finished_at',
        'finish_status_set_at',
        'wonder_completion_level',
        'winning_alliance_id',
        'winning_user_id',
    ];

    protected $casts = [
        'world_started_at' => 'datetime',
        'last_daily_quest_reset_at' => 'datetime',
        'last_medals_given_at' => 'datetime',
        'last_alliance_contribution_reset_at' => 'datetime',
        'artifacts_released_at' => 'datetime',
        'world_finished_at' => 'datetime',
        'finish_status_set_at' => 'datetime',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::create(static::defaultAttributes());
    }

    public static function defaultAttributes(): array
    {
        return [
            'daily_quest_reset_interval_hours' => config('game.daily_quests.reset_interval_hours'),
            'medal_award_interval_hours' => config('game.medals.award_interval_hours'),
            'alliance_contribution_reset_interval_hours' => config('game.alliance.contribution_reset_interval_hours'),
            'wonder_completion_level' => config('game.wonder.completion_level'),
        ];
    }

    public function shouldResetDailyQuests(Carbon $now = null): bool
    {
        $now ??= now();
        $lastReset = $this->last_daily_quest_reset_at;
        if ($lastReset === null) {
            return true;
        }

        return $lastReset->addHours($this->daily_quest_reset_interval_hours)->isPast();
    }

    public function shouldAwardMedals(Carbon $now = null): bool
    {
        $now ??= now();
        $lastAward = $this->last_medals_given_at;
        if ($lastAward === null) {
            return true;
        }

        return $lastAward->addHours($this->medal_award_interval_hours)->isPast();
    }

    public function shouldResetAllianceContributions(Carbon $now = null): bool
    {
        $now ??= now();
        $lastReset = $this->last_alliance_contribution_reset_at;
        if ($lastReset === null) {
            return true;
        }

        return $lastReset->addHours($this->alliance_contribution_reset_interval_hours)->isPast();
    }

    public function markDailyQuestsReset(Carbon $timestamp = null): void
    {
        $this->last_daily_quest_reset_at = $timestamp ?? now();
        $this->save();
    }

    public function markMedalsAwarded(Carbon $timestamp = null): void
    {
        $this->last_medals_given_at = $timestamp ?? now();
        $this->save();
    }

    public function markAllianceContributionsReset(Carbon $timestamp = null): void
    {
        $this->last_alliance_contribution_reset_at = $timestamp ?? now();
        $this->save();
    }

    public function markWorldFinished(?int $winningAllianceId, ?int $winningUserId, Carbon $timestamp = null): void
    {
        $timestamp ??= now();
        $this->world_finished_at = $timestamp;
        $this->finish_status_set_at = $timestamp;
        $this->winning_alliance_id = $winningAllianceId;
        $this->winning_user_id = $winningUserId;
        $this->save();
    }
}
