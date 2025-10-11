<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_adventure_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hero_id')->constrained('hero_profiles');
            $table->string('adventure_type');
            $table->json('rewards')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_adventure_logs');
    }
};
