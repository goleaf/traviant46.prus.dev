<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserDataExportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class UserDataExport extends Model
{
    use HasFactory;
    use Prunable;

    protected $fillable = [
        'user_id',
        'status',
        'disk',
        'file_path',
        'size_bytes',
        'record_count',
        'failure_reason',
        'requested_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'size_bytes' => 'int',
        'record_count' => 'int',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'status' => UserDataExportStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', Carbon::now());
        });
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $query->where('user_id', $userId);
    }

    public function prunable(): Builder
    {
        $threshold = Carbon::now()->subDays((int) config('privacy.export.expires_after_days', 14) + 7);

        return static::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $threshold);
    }

    public function isDownloadable(): bool
    {
        if ($this->status !== UserDataExportStatus::Completed) {
            return false;
        }

        if ($this->expires_at === null) {
            return true;
        }

        return $this->expires_at->isFuture();
    }
}
