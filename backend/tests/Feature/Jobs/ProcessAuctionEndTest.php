<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessAuctionEnd;
use App\Models\Economy\HeroAuction;
use App\Models\Economy\HeroAuctionBid;
use App\Models\Game\HeroAccountEntry;
use App\Models\Game\HeroItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProcessAuctionEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_completes_finished_auctions(): void
    {
        Carbon::setTestNow('2024-01-01 08:00:00');

        $seller = User::factory()->create();
        $winner = User::factory()->create();

        $item = HeroItem::create([
            'user_id' => $seller->id,
            'slot' => 'inventory',
            'type' => 'helmet',
            'rarity' => 'common',
            'quantity' => 1,
            'is_equipped' => false,
        ]);

        $auction = HeroAuction::create([
            'seller_id' => $seller->id,
            'hero_item_id' => $item->id,
            'status' => HeroAuction::STATUS_ACTIVE,
            'starting_bid' => 100,
            'current_bid' => 350,
            'bid_count' => 3,
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subMinutes(10),
        ]);

        $winningBid = HeroAuctionBid::create([
            'auction_id' => $auction->id,
            'bidder_id' => $winner->id,
            'bid_amount' => 350,
            'is_outbid' => false,
            'placed_at' => now()->subMinutes(15),
        ]);

        $auction->update(['winning_bid_id' => $winningBid->id]);

        HeroAuctionBid::create([
            'auction_id' => $auction->id,
            'bidder_id' => $seller->id,
            'bid_amount' => 200,
            'is_outbid' => false,
            'placed_at' => now()->subMinutes(20),
        ]);

        (new ProcessAuctionEnd())->handle();

        $auction->refresh();
        $item->refresh();

        $this->assertSame(HeroAuction::STATUS_COMPLETED, $auction->status);
        $this->assertSame($winner->id, $auction->winner_id);
        $this->assertNotNull($auction->processed_at);

        $this->assertSame($winner->id, $item->user_id);
        $this->assertFalse($item->is_equipped);

        $sellerEntry = HeroAccountEntry::query()
            ->where('user_id', $seller->id)
            ->where('reason', 'auction_sale')
            ->first();
        $this->assertNotNull($sellerEntry);
        $this->assertSame(350, $sellerEntry->silver_delta);

        $buyerEntry = HeroAccountEntry::query()
            ->where('user_id', $winner->id)
            ->where('reason', 'auction_purchase')
            ->first();
        $this->assertNotNull($buyerEntry);
        $this->assertSame(-350, $buyerEntry->silver_delta);

        Carbon::setTestNow();
    }
}
