<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_garrisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_village_id')->constrained('vdata_villages');
            $table->foreignId('target_village_id')->constrained('vdata_villages');
            $table->string('unit_type');
            $table->unsignedInteger('amount')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units_garrisons');
    }
};
