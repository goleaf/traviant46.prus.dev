<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wdata_season_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('wdata_worlds');
            $table->string('season_name');
            $table->date('season_start');
            $table->date('season_end')->nullable();
            $table->json('rules')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wdata_season_configs');
    }
};
