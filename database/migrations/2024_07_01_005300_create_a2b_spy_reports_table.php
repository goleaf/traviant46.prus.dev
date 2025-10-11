<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('a2b_spy_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('path_id')->constrained('movement_paths');
            $table->foreignId('attacker_village_id')->constrained('vdata_villages');
            $table->foreignId('defender_village_id')->constrained('vdata_villages');
            $table->json('intel')->nullable();
            $table->dateTime('reported_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('a2b_spy_reports');
    }
};
