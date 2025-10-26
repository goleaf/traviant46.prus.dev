<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MultiAccountAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'primary_user_id',
        'conflict_user_id',
        'occurrences',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function primaryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_user_id');
    }

    public function conflictUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conflict_user_id');
    }

    public function scopeForIp(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    public function scopeInvolvingUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $query->where(function (Builder $inner) use ($userId) {
            $inner
                ->where('primary_user_id', $userId)
                ->orWhere('conflict_user_id', $userId);
        });
    }

    public function scopeRecent(Builder $query, DateTimeInterface|string $since): Builder
    {
        return $query->where('last_seen_at', '>=', Carbon::parse($since));
    }
}
