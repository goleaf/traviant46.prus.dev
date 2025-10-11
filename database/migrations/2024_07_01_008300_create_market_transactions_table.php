<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('market_listings');
            $table->foreignId('buyer_village_id')->constrained('vdata_villages');
            $table->dateTime('executed_at');
            $table->unsignedInteger('trade_ratio')->default(100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_transactions');
    }
};
