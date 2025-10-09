<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Message extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'alliance_id',
        'subject',
        'body',
        'metadata',
        'is_archived',
        'read_at',
        'sent_at',
        'thread_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'is_archived' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Message $message): void {
            $message->sent_at ??= now();
        });
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    protected function preview(): Attribute
    {
        return Attribute::get(function (): string {
            $body = strip_tags($this->body ?? '');

            return Str::limit(trim($body), 80);
        });
    }

    protected function isRead(): Attribute
    {
        return Attribute::get(fn (): bool => $this->read_at !== null);
    }

    public function scopeInboxFor(Builder $query, int $userId): Builder
    {
        return $query->where('recipient_id', $userId);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeBetween(Builder $query, int $userId, int $otherUserId): Builder
    {
        return $query->where(function (Builder $builder) use ($userId, $otherUserId): void {
            $builder
                ->where('sender_id', $userId)
                ->where('recipient_id', $otherUserId);
        })->orWhere(function (Builder $builder) use ($userId, $otherUserId): void {
            $builder
                ->where('sender_id', $otherUserId)
                ->where('recipient_id', $userId);
        });
    }
}
