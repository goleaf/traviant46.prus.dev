<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_auctions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hero_item_id')->nullable()->constrained('hero_items')->nullOnDelete();
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('winning_bid_id')->nullable();
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled', 'expired'])->default('pending');
            $table->unsignedBigInteger('starting_bid')->default(0);
            $table->unsignedBigInteger('current_bid')->default(0);
            $table->unsignedBigInteger('buyout_price')->nullable();
            $table->unsignedInteger('bid_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_auctions');
    }
};
