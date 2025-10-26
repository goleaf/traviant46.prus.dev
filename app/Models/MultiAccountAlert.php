<?php

namespace App\Models;

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MultiAccountAlert extends Model
{
    use HasFactory;
    use Prunable;

    private const ARCHIVE_AFTER_DAYS = 180;

    protected $fillable = [
        'alert_id',
        'group_key',
        'source_type',
        'ip_address',
        'device_hash',
        'user_ids',
        'occurrences',
        'first_seen_at',
        'window_started_at',
        'last_seen_at',
        'severity',
        'status',
        'suppression_reason',
        'resolved_at',
        'resolved_by_user_id',
        'dismissed_at',
        'dismissed_by_user_id',
        'notes',
        'metadata',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'user_ids' => 'array',
            'metadata' => 'array',
            'first_seen_at' => 'datetime',
            'window_started_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'resolved_at' => 'datetime',
            'dismissed_at' => 'datetime',
            'last_notified_at' => 'datetime',
            'severity' => MultiAccountAlertSeverity::class,
            'status' => MultiAccountAlertStatus::class,
        ];
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function dismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by_user_id');
    }

    public function scopeForIp(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    public function scopeForDevice(Builder $query, string $deviceHash): Builder
    {
        return $query->where('device_hash', $deviceHash);
    }

    public function scopeInvolvingUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $query->whereJsonContains('user_ids', $userId);
    }

    public function scopeRecent(Builder $query, DateTimeInterface|string $since): Builder
    {
        return $query->where('last_seen_at', '>=', Carbon::parse($since));
    }

    public function scopeWithStatus(Builder $query, MultiAccountAlertStatus|string $status): Builder
    {
        $value = $status instanceof MultiAccountAlertStatus ? $status->value : (string) $status;

        return $query->where('status', $value);
    }

    public function prunable(): Builder
    {
        return static::query()
            ->where('last_seen_at', '<', Carbon::now()->subDays(self::ARCHIVE_AFTER_DAYS));
    }
}
