<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Market extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'market_offers';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'village_id',
        'user_id',
        'offered_resources',
        'requested_resources',
        'merchant_count',
        'expires_at',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'village_id' => 'integer',
        'user_id' => 'integer',
        'offered_resources' => 'array',
        'requested_resources' => 'array',
        'merchant_count' => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * Scope only offers that are currently open and not expired.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', 'open')
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope offers that will expire within the provided number of minutes.
     */
    public function scopeExpiringWithin(Builder $query, int $minutes): Builder
    {
        $threshold = now()->addMinutes($minutes);

        return $query
            ->where('status', 'open')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), $threshold]);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function isExpired(): Attribute
    {
        return Attribute::get(function (): bool {
            if (!$this->expires_at instanceof Carbon) {
                return false;
            }

            return $this->expires_at->isPast();
        });
    }

    protected function totalOffered(): Attribute
    {
        return Attribute::get(function (): int {
            $resources = $this->offered_resources ?? [];

            if (!is_array($resources)) {
                return 0;
            }

            return array_sum(array_map('intval', $resources));
        });
    }

    protected function totalRequested(): Attribute
    {
        return Attribute::get(function (): int {
            $resources = $this->requested_resources ?? [];

            if (!is_array($resources)) {
                return 0;
            }

            return array_sum(array_map('intval', $resources));
        });
    }
}
