<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Auction extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'hero_auctions';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'seller_id',
        'winner_id',
        'hero_item_id',
        'starting_bid',
        'current_bid',
        'buyout_price',
        'bid_count',
        'status',
        'starts_at',
        'ends_at',
        'completed_at',
        'metadata',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'seller_id' => 'integer',
        'winner_id' => 'integer',
        'hero_item_id' => 'integer',
        'starting_bid' => 'integer',
        'current_bid' => 'integer',
        'buyout_price' => 'integer',
        'bid_count' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Auctions that are visible to players.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    /**
     * Auctions ending within the provided timeframe.
     */
    public function scopeEndingWithin(Builder $query, int $minutes): Builder
    {
        $now = now();
        $cutoff = $now->copy()->addMinutes($minutes);

        return $query
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [$now, $cutoff]);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(HeroItem::class, 'hero_item_id');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    protected function isCompleted(): Attribute
    {
        return Attribute::get(function (): bool {
            if (!in_array($this->status, ['completed', 'cancelled', 'expired'], true)) {
                return false;
            }

            if ($this->completed_at instanceof Carbon) {
                return $this->completed_at->isPast();
            }

            if ($this->ends_at instanceof Carbon) {
                return $this->ends_at->isPast();
            }

            return true;
        });
    }

    protected function remainingSeconds(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (!$this->ends_at instanceof Carbon) {
                return null;
            }

            return $this->ends_at->isPast() ? 0 : now()->diffInSeconds($this->ends_at, false);
        });
    }
}
