<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_auction_bids', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('auction_id')->constrained('hero_auctions')->cascadeOnDelete();
            $table->foreignId('bidder_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('bid_amount');
            $table->boolean('is_outbid')->default(false);
            $table->timestamp('placed_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['auction_id', 'placed_at']);
            $table->index(['bidder_id', 'placed_at']);
        });

        Schema::table('hero_auctions', function (Blueprint $table): void {
            $table
                ->foreign('winning_bid_id')
                ->references('id')
                ->on('hero_auction_bids')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hero_auctions', function (Blueprint $table): void {
            $table->dropForeign(['winning_bid_id']);
        });

        Schema::dropIfExists('hero_auction_bids');
    }
};
