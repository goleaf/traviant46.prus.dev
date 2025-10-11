<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Bid extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'hero_auction_bids';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'auction_id',
        'bidder_id',
        'bid_amount',
        'is_outbid',
        'placed_at',
        'silver_balance_snapshot',
        'metadata',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'auction_id' => 'integer',
        'bidder_id' => 'integer',
        'bid_amount' => 'integer',
        'is_outbid' => 'boolean',
        'placed_at' => 'datetime',
        'silver_balance_snapshot' => 'array',
        'metadata' => 'array',
    ];

    /**
     * @var array<int, string>
     */
    protected $touches = ['auction'];

    /**
     * Retrieve bids ordered from newest to oldest for a given auction.
     */
    public function scopeForAuction(Builder $query, int $auctionId): Builder
    {
        return $query
            ->where('auction_id', $auctionId)
            ->orderByDesc('placed_at');
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function bidder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bidder_id');
    }

    protected function placedAgo(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (!$this->placed_at instanceof Carbon) {
                return null;
            }

            return $this->placed_at->diffInMinutes(now());
        });
    }

    protected function wasWinning(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->is_outbid) {
                return false;
            }

            $auction = $this->auction;
            if (!$auction instanceof Auction) {
                return false;
            }

            return $auction->current_bid === $this->bid_amount
                && $auction->winner_id === $this->bidder_id;
        });
    }
}
