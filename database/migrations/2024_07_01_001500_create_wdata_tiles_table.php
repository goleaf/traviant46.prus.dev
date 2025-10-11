<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wdata_tiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('wdata_regions');
            $table->unsignedInteger('x_coordinate');
            $table->unsignedInteger('y_coordinate');
            $table->string('terrain_type');
            $table->unique(['region_id', 'x_coordinate', 'y_coordinate']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wdata_tiles');
    }
};
