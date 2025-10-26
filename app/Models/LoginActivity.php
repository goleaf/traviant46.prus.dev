<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Security\IpAnonymizer;
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

    protected $fillable = [
        'user_id',
        'acting_sitter_id',
        'ip_address',
        'ip_address_hash',
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

    public function scopeFromIpHash(Builder $query, string $ipHash): Builder
    {
        return $query->where('ip_address_hash', $ipHash);
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
        $retentionDays = $this->retentionDays();

        if ($retentionDays <= 0) {
            return static::query()->whereRaw('0 = 1');
        }

        $threshold = Carbon::now()->subDays($retentionDays);

        return static::query()->where('logged_at', '<', $threshold);
    }

    protected function retentionDays(): int
    {
        $privacyRetention = config('privacy.ip.retention.login_activities', []);
        $privacyDays = (int) ($privacyRetention['delete_after_days'] ?? 365);
        $multiAccountDays = (int) config('multiaccount.pruning.login_activity_days', $privacyDays);

        $candidates = array_filter([$privacyDays, $multiAccountDays], static fn (int $value): bool => $value > 0);

        if ($candidates === []) {
            return 0;
        }

        return min($candidates);
    }
}
