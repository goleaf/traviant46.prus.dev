<?php

namespace App\Jobs;

use App\Models\Economy\HeroAuction;
use App\Models\Economy\HeroAuctionBid;
use App\Models\Game\HeroAccountEntry;
use App\Models\Game\HeroItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAuctionEnd implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(private readonly int $chunkSize = 100)
    {
        $this->onQueue('automation');
    }

    public function handle(): void
    {
        HeroAuction::query()
            ->dueForCompletion()
            ->orderBy('ends_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (HeroAuction $auction): void {
                $this->finaliseAuction($auction);
            });
    }

    private function finaliseAuction(HeroAuction $auction): void
    {
        try {
            DB::transaction(function () use ($auction): void {
                $lockedAuction = HeroAuction::query()
                    ->whereKey($auction->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedAuction === null) {
                    return;
                }

                if ($lockedAuction->processed_at !== null) {
                    return;
                }

                if ($lockedAuction->status !== HeroAuction::STATUS_ACTIVE) {
                    return;
                }

                if ($lockedAuction->ends_at?->isFuture()) {
                    return;
                }

                $winningBid = $lockedAuction->winningBid;

                if ($winningBid === null) {
                    $lockedAuction->markExpired();
                    $lockedAuction->save();

                    return;
                }

                $this->transferItemToWinner($lockedAuction, $winningBid);
                $this->recordAccountingEntries($lockedAuction, $winningBid);

                HeroAuctionBid::query()
                    ->where('auction_id', $lockedAuction->getKey())
                    ->where('id', '!=', $winningBid->getKey())
                    ->update(['is_outbid' => true]);

                $lockedAuction->markCompleted($winningBid->bidder_id, $winningBid->getKey());
                $lockedAuction->save();
            }, 5);
        } catch (Throwable $throwable) {
            Log::error('Failed to finalise hero auction.', [
                'hero_auction_id' => $auction->getKey(),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    private function transferItemToWinner(HeroAuction $auction, HeroAuctionBid $winningBid): void
    {
        if ($auction->hero_item_id === null) {
            return;
        }

        $item = HeroItem::query()
            ->whereKey($auction->hero_item_id)
            ->lockForUpdate()
            ->first();

        if ($item === null) {
            Log::warning('Auction item missing during finalisation.', [
                'hero_auction_id' => $auction->getKey(),
                'hero_item_id' => $auction->hero_item_id,
            ]);

            return;
        }

        $item->user_id = $winningBid->bidder_id;
        $item->hero_id = null;
        $item->is_equipped = false;
        $item->save();
    }

    private function recordAccountingEntries(HeroAuction $auction, HeroAuctionBid $winningBid): void
    {
        $now = now();

        HeroAccountEntry::create([
            'user_id' => $auction->seller_id,
            'hero_id' => null,
            'reason' => 'auction_sale',
            'gold_delta' => 0,
            'silver_delta' => (int) $auction->current_bid,
            'details' => [
                'auction_id' => $auction->getKey(),
                'winning_bid_id' => $winningBid->getKey(),
            ],
            'recorded_at' => $now,
        ]);

        HeroAccountEntry::create([
            'user_id' => $winningBid->bidder_id,
            'hero_id' => null,
            'reason' => 'auction_purchase',
            'gold_delta' => 0,
            'silver_delta' => -((int) $auction->current_bid),
            'details' => [
                'auction_id' => $auction->getKey(),
                'winning_bid_id' => $winningBid->getKey(),
            ],
            'recorded_at' => $now,
        ]);
    }
}
