<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wdata_tiles_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tile_id')->constrained('wdata_tiles');
            $table->unsignedInteger('wood');
            $table->unsignedInteger('clay');
            $table->unsignedInteger('iron');
            $table->unsignedInteger('crop');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wdata_tiles_resources');
    }
};
