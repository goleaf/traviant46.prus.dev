<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_daily_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('wdata_worlds');
            $table->date('recorded_on');
            $table->unsignedInteger('wood_price');
            $table->unsignedInteger('clay_price');
            $table->unsignedInteger('iron_price');
            $table->unsignedInteger('crop_price');
            $table->unique(['world_id', 'recorded_on']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_daily_prices');
    }
};
