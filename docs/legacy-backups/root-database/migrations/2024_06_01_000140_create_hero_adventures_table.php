<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_adventures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hero_id')->constrained('heroes')->cascadeOnDelete();
            $table->unsignedBigInteger('origin_village_id')->nullable()->index();
            $table->unsignedBigInteger('target_village_id')->nullable()->index();
            $table->enum('difficulty', ['easy', 'normal', 'hard'])->default('normal');
            $table->enum('type', ['resource', 'equipment', 'experience', 'unique'])->default('resource');
            $table->enum('status', ['available', 'in_progress', 'completed', 'failed', 'expired'])->default('available');
            $table->timestamp('available_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('rewards')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['hero_id', 'status']);
            $table->index(['status', 'available_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_adventures');
    }
};
