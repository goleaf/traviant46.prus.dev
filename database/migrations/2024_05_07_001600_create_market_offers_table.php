<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('offered_resources')->nullable();
            $table->json('requested_resources')->nullable();
            $table->unsignedTinyInteger('merchant_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['open', 'accepted', 'cancelled', 'expired'])->default('open');
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_offers');
    }
};
