<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wdata_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('wdata_worlds');
            $table->string('region_code');
            $table->string('region_name');
            $table->unique(['world_id', 'region_code']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wdata_regions');
    }
};
