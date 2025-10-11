<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vdata_population_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('wdata_worlds');
            $table->foreignId('village_id')->constrained('vdata_villages');
            $table->unsignedInteger('rank');
            $table->date('ranking_date');
            $table->unique(['world_id', 'ranking_date', 'village_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vdata_population_rankings');
    }
};
