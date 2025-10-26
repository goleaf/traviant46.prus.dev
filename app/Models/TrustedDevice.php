<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TrustedDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'public_id',
        'label',
        'token_hash',
        'fingerprint_hash',
        'ip_address',
        'user_agent',
        'first_trusted_at',
        'last_used_at',
        'expires_at',
        'revoked_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'first_trusted_at' => 'datetime',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query
            ->whereNull('revoked_at')
            ->where(function (Builder $builder) use ($now) {
                $builder
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            });
    }

    public function scopeMatchingToken(Builder $query, string $tokenHash): Builder
    {
        return $query->where('token_hash', $tokenHash);
    }

    public function scopeMatchingFingerprint(Builder $query, ?string $fingerprintHash): Builder
    {
        if ($fingerprintHash === null) {
            return $query->whereNull('fingerprint_hash');
        }

        return $query->where('fingerprint_hash', $fingerprintHash);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function hasExpired(?Carbon $at = null): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isBefore($at ?? Carbon::now());
    }

    public function isActive(?Carbon $at = null): bool
    {
        if ($this->isRevoked()) {
            return false;
        }

        return ! $this->hasExpired($at);
    }
}
