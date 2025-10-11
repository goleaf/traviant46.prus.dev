<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_waypoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('path_id')->constrained('movement_paths');
            $table->unsignedInteger('sequence');
            $table->foreignId('tile_id')->constrained('wdata_tiles');
            $table->unsignedInteger('arrival_offset')->default(0);
            $table->unique(['path_id', 'sequence']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_waypoints');
    }
};
