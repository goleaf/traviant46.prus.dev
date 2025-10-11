<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('vdata_villages');
            $table->string('equipment_type');
            $table->unsignedInteger('quantity')->default(0);
            $table->json('attributes')->nullable();
            $table->unique(['village_id', 'equipment_type']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units_equipment');
    }
};
