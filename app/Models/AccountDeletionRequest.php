<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AccountDeletionRequest extends Model
{
    use HasFactory;
    use Prunable;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'status',
        'requested_at',
        'scheduled_for',
        'processed_at',
        'cancelled_at',
        'request_ip',
        'request_ip_hash',
        'notes',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'processed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReadyToProcess(Builder $query, ?Carbon $at = null): Builder
    {
        $moment = $at ?? Carbon::now();

        return $query
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS])
            ->where('scheduled_for', '<=', $moment);
    }

    public function prunable(): Builder
    {
        return static::query()
            ->where('status', self::STATUS_COMPLETED)
            ->where('processed_at', '<', Carbon::now()->subYear());
    }

    public function isCancelable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS], true)
            && $this->scheduled_for?->isFuture();
    }
}
