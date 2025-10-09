<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'quest_definition_id',
        'progress_payload',
        'accepted_at',
        'completed_at',
    ];

    protected $casts = [
        'progress_payload' => 'array',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(QuestDefinition::class, 'quest_definition_id');
    }
}
