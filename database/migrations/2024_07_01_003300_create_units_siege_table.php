<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_siege', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('vdata_villages');
            $table->string('unit_type');
            $table->unsignedInteger('current')->default(0);
            $table->unsignedInteger('training')->default(0);
            $table->unique(['village_id', 'unit_type']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units_siege');
    }
};
