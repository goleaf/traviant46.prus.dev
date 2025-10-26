<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int|null $legacy_message_id
 * @property int|null $sender_id
 * @property string $subject
 * @property string $body
 * @property string $message_type
 * @property string $delivery_scope
 * @property bool $is_system_generated
 * @property bool $is_broadcast
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MessageRecipient> $recipients
 */
class Message extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'legacy_message_id',
        'sender_id',
        'alliance_id',
        'subject',
        'body',
        'message_type',
        'delivery_scope',
        'is_system_generated',
        'is_broadcast',
        'checksum',
        'sent_at',
        'delivered_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'is_system_generated' => 'bool',
            'is_broadcast' => 'bool',
            'metadata' => 'array',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MessageRecipient::class);
    }

    public function scopeDispatched(Builder $query): Builder
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopeBetweenUsers(Builder $query, int $senderId, int $recipientId): Builder
    {
        return $query->where(function (Builder $inner) use ($senderId, $recipientId): void {
            $inner->where('sender_id', $senderId)
                ->whereHas('recipients', static function (Builder $recipientQuery) use ($recipientId): void {
                    $recipientQuery->where('recipient_id', $recipientId);
                });
        });
    }

    public function scopeForRecipient(Builder $query, User|int $recipient): Builder
    {
        $recipientId = $recipient instanceof User ? $recipient->getKey() : $recipient;

        return $query->whereHas('recipients', static function (Builder $recipientQuery) use ($recipientId): void {
            $recipientQuery->where('recipient_id', $recipientId);
        });
    }
}
