<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TradeRoute extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'origin_village_id',
        'target_village_id',
        'resources',
        'dispatch_interval_minutes',
        'next_dispatch_at',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'origin_village_id' => 'integer',
        'target_village_id' => 'integer',
        'resources' => 'array',
        'dispatch_interval_minutes' => 'integer',
        'next_dispatch_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Scope active routes.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope routes originating from the provided village.
     */
    public function scopeForVillage(Builder $query, int $villageId): Builder
    {
        return $query->where('origin_village_id', $villageId);
    }

    /**
     * Scope routes that should dispatch up to the provided timestamp.
     */
    public function scopeDueBy(Builder $query, Carbon $moment): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereNotNull('next_dispatch_at')
            ->where('next_dispatch_at', '<=', $moment);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function originVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'origin_village_id');
    }

    public function targetVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'target_village_id');
    }

    protected function nextDispatchInMinutes(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (!$this->next_dispatch_at instanceof Carbon) {
                return null;
            }

            return $this->next_dispatch_at->isPast()
                ? 0
                : now()->diffInMinutes($this->next_dispatch_at, false);
        });
    }

    protected function totalResources(): Attribute
    {
        return Attribute::get(function (): int {
            $resources = $this->resources ?? [];

            if (!is_array($resources)) {
                return 0;
            }

            return array_sum(array_map('intval', $resources));
        });
    }
}
