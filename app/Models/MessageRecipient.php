<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $message_id
 * @property int $recipient_id
 * @property string $status
 * @property bool $is_archived
 * @property bool $is_muted
 * @property bool $is_reported
 * @property array<string, mixed>|null $flags
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $reported_at
 */
class MessageRecipient extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'message_id',
        'recipient_id',
        'recipient_alliance_id',
        'status',
        'is_archived',
        'is_muted',
        'is_reported',
        'read_at',
        'archived_at',
        'deleted_at',
        'reported_at',
        'flags',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
            'reported_at' => 'datetime',
            'is_archived' => 'bool',
            'is_muted' => 'bool',
            'is_reported' => 'bool',
            'flags' => 'array',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', 'unread')->whereNull('read_at');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }
}
