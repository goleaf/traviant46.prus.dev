<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_training_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('vdata_villages');
            $table->string('unit_category');
            $table->unsignedInteger('amount');
            $table->dateTime('finishes_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units_training_queue');
    }
};
