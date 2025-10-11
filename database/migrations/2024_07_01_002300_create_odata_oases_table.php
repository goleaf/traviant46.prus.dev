<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odata_oases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('wdata_worlds');
            $table->foreignId('tile_id')->unique()->constrained('wdata_tiles');
            $table->string('oasis_type');
            $table->boolean('is_occupied')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odata_oases');
    }
};
