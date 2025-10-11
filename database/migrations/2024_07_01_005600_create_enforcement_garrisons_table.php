<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enforcement_garrisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('vdata_villages');
            $table->foreignId('source_village_id')->constrained('vdata_villages');
            $table->string('unit_type');
            $table->unsignedInteger('amount')->default(0);
            $table->dateTime('arrived_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enforcement_garrisons');
    }
};
