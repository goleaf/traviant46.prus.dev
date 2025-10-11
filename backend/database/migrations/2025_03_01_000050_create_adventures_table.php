<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adventures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hero_id')->nullable()->constrained('heroes')->nullOnDelete();
            $table->foreignId('origin_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->foreignId('target_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->enum('difficulty', ['easy', 'normal', 'hard'])->default('normal');
            $table->enum('adventure_type', ['resource', 'equipment', 'experience', 'unique'])->default('resource');
            $table->enum('status', ['available', 'pending', 'in_progress', 'completed', 'failed', 'expired'])
                ->default('pending');
            $table->json('reward')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completes_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'completes_at']);
            $table->index(['hero_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adventures');
    }
};
