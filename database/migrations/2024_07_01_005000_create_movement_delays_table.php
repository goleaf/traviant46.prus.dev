<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_delays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('path_id')->constrained('movement_paths');
            $table->string('reason');
            $table->unsignedInteger('delay_seconds')->default(0);
            $table->dateTime('applied_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_delays');
    }
};
