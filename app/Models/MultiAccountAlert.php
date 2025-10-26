<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MultiAccountAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_id',
        'group_key',
        'ip_address',
        'user_ids',
        'first_seen_at',
        'last_seen_at',
        'severity',
    ];

    protected $casts = [
        'user_ids' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function scopeForIp(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
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
}
