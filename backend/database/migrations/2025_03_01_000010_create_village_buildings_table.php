<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('building_type_id')->constrained('building_types')->cascadeOnDelete();
            $table->unsignedTinyInteger('level')->default(0);
            $table->timestamps();

            $table->unique(['village_id', 'building_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_buildings');
    }
};
