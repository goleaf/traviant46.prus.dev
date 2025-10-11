<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_casualties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('vdata_villages');
            $table->string('unit_type');
            $table->unsignedInteger('lost')->default(0);
            $table->dateTime('recorded_at');
            $table->index(['village_id', 'recorded_at']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units_casualties');
    }
};
