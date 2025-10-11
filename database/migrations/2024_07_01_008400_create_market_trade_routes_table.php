<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_trade_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_village_id')->constrained('vdata_villages');
            $table->foreignId('target_village_id')->constrained('vdata_villages');
            $table->unsignedInteger('wood')->default(0);
            $table->unsignedInteger('clay')->default(0);
            $table->unsignedInteger('iron')->default(0);
            $table->unsignedInteger('crop')->default(0);
            $table->time('departure_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_trade_routes');
    }
};
