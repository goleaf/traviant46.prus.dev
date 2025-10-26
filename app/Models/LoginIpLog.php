<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Security\IpAnonymizer;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class LoginIpLog extends Model
{
    use Prunable;

    protected $fillable = [
        'user_id',
        'ip_address',
        'ip_address_hash',
        'ip_address_numeric',
        'reputation_score',
        'reputation_details',
        'recorded_at',
    ];

    protected $casts = [
        'user_id' => 'int',
        'ip_address_numeric' => 'int',
        'reputation_score' => 'int',
        'reputation_details' => 'array',
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
        /** @var IpAnonymizer $anonymizer */
        $anonymizer = app(IpAnonymizer::class);
        $hash = $anonymizer->anonymize($ipAddress);

        return $query->where(function (Builder $builder) use ($ipAddress, $hash): void {
            $builder->where('ip_address', $ipAddress);

            if ($hash !== null) {
                $builder->orWhere('ip_address_hash', $hash);
            }
        });
    }

    public function scopeFromIpHash(Builder $query, string $hash): Builder
    {
        return $query->where('ip_address_hash', $hash);
    }

    public function scopeRecordedBetween(Builder $query, DateTimeInterface|string $from, DateTimeInterface|string $to): Builder
    {
        return $query
            ->where('recorded_at', '>=', Carbon::parse($from))
            ->where('recorded_at', '<=', Carbon::parse($to));
    }

    public function prunable(): Builder
    {
        $retention = config('privacy.ip.retention.login_ip_logs', []);
        $deleteAfter = (int) ($retention['delete_after_days'] ?? 730);

        if ($deleteAfter <= 0) {
            return static::query()->whereRaw('0 = 1');
        }

        $threshold = Carbon::now()->subDays($deleteAfter);

        return static::query()->where('recorded_at', '<', $threshold);
    }
}
