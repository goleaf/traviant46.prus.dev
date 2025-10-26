<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountDeletionRequestStatus;
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
        'status' => AccountDeletionRequestStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AccountDeletionRequestStatus::Pending);
    }

    public function scopeReadyToProcess(Builder $query, ?Carbon $at = null): Builder
    {
        $moment = $at ?? Carbon::now();

        return $query
            ->whereIn('status', [AccountDeletionRequestStatus::Pending, AccountDeletionRequestStatus::InProgress])
            ->where('scheduled_for', '<=', $moment);
    }

    public function prunable(): Builder
    {
        return static::query()
            ->where('status', AccountDeletionRequestStatus::Completed)
            ->where('processed_at', '<', Carbon::now()->subYear());
    }

    public function isCancelable(): bool
    {
        return in_array($this->status, [AccountDeletionRequestStatus::Pending, AccountDeletionRequestStatus::InProgress], true)
            && $this->scheduled_for?->isFuture();
    }
}
