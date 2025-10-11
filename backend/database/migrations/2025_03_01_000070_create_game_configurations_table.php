<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_configurations', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('world_started_at')->nullable();
            $table->timestamp('last_daily_quest_reset_at')->nullable();
            $table->unsignedInteger('daily_quest_reset_interval_hours')->default(24);
            $table->timestamp('last_medals_given_at')->nullable();
            $table->unsignedInteger('medal_award_interval_hours')->default(168);
            $table->timestamp('last_alliance_contribution_reset_at')->nullable();
            $table->unsignedInteger('alliance_contribution_reset_interval_hours')->default(24);
            $table->timestamp('artifacts_released_at')->nullable();
            $table->timestamp('world_finished_at')->nullable();
            $table->timestamp('finish_status_set_at')->nullable();
            $table->unsignedInteger('wonder_completion_level')->default(100);
            $table->unsignedBigInteger('winning_alliance_id')->nullable();
            $table->foreignId('winning_user_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_configurations');
    }
};
