<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('wdata_worlds');
            $table->foreignId('origin_village_id')->constrained('vdata_villages');
            $table->foreignId('target_village_id')->constrained('vdata_villages');
            $table->unsignedInteger('duration_seconds');
            $table->string('movement_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_paths');
    }
};
