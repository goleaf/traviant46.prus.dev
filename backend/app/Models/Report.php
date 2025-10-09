<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_player_id',
        'recipient_player_id',
        'origin_village_id',
        'target_village_id',
        'report_type',
        'subject',
        'body',
        'payload',
        'sent_at',
        'read_at',
        'archived_by_recipient',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'archived_by_recipient' => 'boolean',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'sender_player_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'recipient_player_id');
    }

    public function originVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'origin_village_id');
    }

    public function targetVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'target_village_id');
    }
}
