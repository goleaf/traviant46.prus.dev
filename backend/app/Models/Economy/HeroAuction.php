<?php

namespace App\Models\Economy;

use App\Models\Game\HeroItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HeroAuction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'seller_id',
        'hero_item_id',
        'winner_id',
        'winning_bid_id',
        'status',
        'starting_bid',
        'current_bid',
        'buyout_price',
        'bid_count',
        'starts_at',
        'ends_at',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'starting_bid' => 'integer',
        'current_bid' => 'integer',
        'buyout_price' => 'integer',
        'bid_count' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function scopeDueForCompletion(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->whereNull('processed_at')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now());
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

    public function winningBid(): BelongsTo
    {
        return $this->belongsTo(HeroAuctionBid::class, 'winning_bid_id');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(HeroAuctionBid::class, 'auction_id');
    }

    public function markExpired(): void
    {
        $this->status = self::STATUS_EXPIRED;
        $this->processed_at = now();
    }

    public function markCompleted(int $winnerId, int $winningBidId): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->winner_id = $winnerId;
        $this->winning_bid_id = $winningBidId;
        $this->processed_at = now();
    }
}
