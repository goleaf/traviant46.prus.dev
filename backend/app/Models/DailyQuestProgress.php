<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyQuestProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'quest_date',
        'completed_tasks',
        'tasks_payload',
        'reset_at',
    ];

    protected $casts = [
        'quest_date' => 'date',
        'completed_tasks' => 'integer',
        'tasks_payload' => 'array',
        'reset_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
