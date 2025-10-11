<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vdata_villages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('wdata_worlds');
            $table->foreignId('tile_id')->unique()->constrained('wdata_tiles');
            $table->foreignId('owner_id')->nullable()->constrained('users');
            $table->string('village_name');
            $table->unsignedInteger('population')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vdata_villages');
    }
};
