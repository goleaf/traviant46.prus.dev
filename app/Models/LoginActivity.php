<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class LoginActivity extends Model
{
    use HasFactory;
    use Prunable;

    private const PRUNING_WINDOW_DAYS = 90;

    protected $fillable = [
        'user_id',
        'acting_sitter_id',
        'ip_address',
        'user_agent',
        'device_hash',
        'geo',
        'logged_at',
        'via_sitter',
    ];

    protected $casts = [
        'via_sitter' => 'boolean',
        'geo' => 'array',
        'logged_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actingSitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acting_sitter_id');
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $query->where('user_id', $userId);
    }

    public function scopeExceptUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $query->where('user_id', '!=', $userId);
    }

    public function scopeFromIp(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    public function scopeViaSitter(Builder $query, bool $viaSitter = true): Builder
    {
        return $query->where('via_sitter', $viaSitter);
    }

    public function scopeWithin(Builder $query, DateTimeInterface|string $start, DateTimeInterface|string|null $end = null): Builder
    {
        $startAt = Carbon::parse($start);

        $query->where('logged_at', '>=', $startAt);

        if ($end !== null) {
            $query->where('logged_at', '<=', Carbon::parse($end));
        }

        return $query;
    }

    public function prunable(): Builder
    {
        return static::query()
            ->where('logged_at', '<', Carbon::now()->subDays(self::PRUNING_WINDOW_DAYS));
    }
}
