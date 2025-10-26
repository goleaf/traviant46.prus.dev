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
            $table->unsignedTinyInteger('slot_number');
            $table->unsignedSmallInteger('building_type')->nullable();
            $table->unsignedTinyInteger('level')->default(0);
            $table->timestamps();
            $table->unique(['village_id', 'slot_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_buildings');
    }
};
