<?php

namespace App\Models\Economy;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroAuctionBid extends Model
{
    use HasFactory;

    protected $fillable = [
        'auction_id',
        'bidder_id',
        'bid_amount',
        'is_outbid',
        'metadata',
        'placed_at',
    ];

    protected $casts = [
        'bid_amount' => 'integer',
        'is_outbid' => 'boolean',
        'metadata' => 'array',
        'placed_at' => 'datetime',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(HeroAuction::class, 'auction_id');
    }

    public function bidder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bidder_id');
    }
}
