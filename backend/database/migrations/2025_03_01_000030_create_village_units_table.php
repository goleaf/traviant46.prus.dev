<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->unsignedSmallInteger('unit_type_id');
            $table->unsignedBigInteger('quantity')->default(0);
            $table->timestamps();
            $table->unique(['village_id', 'unit_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_units');
    }
};
