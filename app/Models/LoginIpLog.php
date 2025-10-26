<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class LoginIpLog extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'ip_address_numeric',
        'recorded_at',
    ];

    protected $casts = [
        'user_id' => 'int',
        'ip_address_numeric' => 'int',
        'recorded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $query->where('user_id', $userId);
    }

    public function scopeFromIp(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    public function scopeRecordedBetween(Builder $query, DateTimeInterface|string $from, DateTimeInterface|string $to): Builder
    {
        return $query
            ->where('recorded_at', '>=', Carbon::parse($from))
            ->where('recorded_at', '<=', Carbon::parse($to));
    }
}
