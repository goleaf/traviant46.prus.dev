<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artifact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'artifact_type',
        'owner_id',
        'effect_payload',
        'effect_interval_minutes',
        'last_effect_applied_at',
        'next_effect_at',
    ];

    protected $casts = [
        'effect_payload' => 'array',
        'last_effect_applied_at' => 'datetime',
        'next_effect_at' => 'datetime',
        'effect_interval_minutes' => 'integer',
    ];

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->whereNotNull('next_effect_at')
            ->where('next_effect_at', '<=', now());
    }

    public function scheduleNextEffect(): void
    {
        $interval = $this->effect_interval_minutes ?: config('game.artifacts.default_effect_interval_minutes');
        $this->last_effect_applied_at = now();
        $this->next_effect_at = now()->addMinutes($interval);
        $this->save();
    }
}
