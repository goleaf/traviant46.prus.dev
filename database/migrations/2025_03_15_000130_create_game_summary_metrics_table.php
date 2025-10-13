<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_summary_metrics', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('total_player_count')->default(0);
            $table->unsignedInteger('roman_player_count')->default(0);
            $table->unsignedInteger('teuton_player_count')->default(0);
            $table->unsignedInteger('gaul_player_count')->default(0);
            $table->unsignedInteger('egyptian_player_count')->default(0);
            $table->unsignedInteger('hun_player_count')->default(0);
            $table->string('first_village_player_name')->nullable();
            $table->timestamp('first_village_recorded_at')->nullable();
            $table->string('first_artifact_player_name')->nullable();
            $table->timestamp('first_artifact_recorded_at')->nullable();
            $table->string('first_world_wonder_plan_player_name')->nullable();
            $table->timestamp('first_world_wonder_plan_recorded_at')->nullable();
            $table->string('first_world_wonder_player_name')->nullable();
            $table->timestamp('first_world_wonder_recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_summary_metrics');
    }
};
