<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyQuest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quest_key',
        'progress',
        'current_step',
        'points',
        'completed_at',
        'reward_claimed_at',
    ];

    protected $casts = [
        'progress' => 'array',
        'completed_at' => 'datetime',
        'reward_claimed_at' => 'datetime',
    ];

    public function scopeWithProgress(Builder $query): Builder
    {
        return $query->whereNotNull('progress');
    }

    public function resetProgress(): void
    {
        $this->progress = null;
        $this->current_step = 0;
        $this->points = 0;
        $this->completed_at = null;
        $this->reward_claimed_at = null;
        $this->save();
    }
}
