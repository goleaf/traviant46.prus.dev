<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_upkeep', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('vdata_villages');
            $table->unsignedInteger('crop_consumption')->default(0);
            $table->unsignedInteger('crop_production')->default(0);
            $table->date('calculated_on');
            $table->unique(['village_id', 'calculated_on']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_upkeep');
    }
};
