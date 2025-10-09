<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('summary');

        Schema::create('world_summaries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('player_count')->default(0);
            $table->unsignedInteger('roman_player_count')->default(0);
            $table->unsignedInteger('teuton_player_count')->default(0);
            $table->unsignedInteger('gaul_player_count')->default(0);
            $table->unsignedInteger('egyptian_player_count')->default(0);
            $table->unsignedInteger('hun_player_count')->default(0);
            $table->foreignId('first_village_player_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_village_player_name')->nullable();
            $table->timestamp('first_village_recorded_at')->nullable();
            $table->foreignId('first_artifact_player_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_artifact_player_name')->nullable();
            $table->timestamp('first_artifact_recorded_at')->nullable();
            $table->foreignId('first_plan_player_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_plan_player_name')->nullable();
            $table->timestamp('first_plan_recorded_at')->nullable();
            $table->foreignId('first_wonder_player_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_wonder_player_name')->nullable();
            $table->timestamp('first_wonder_recorded_at')->nullable();
            $table->timestamps();

            $table->index(['player_count', 'roman_player_count', 'teuton_player_count']);
            $table->index(['first_village_recorded_at', 'first_artifact_recorded_at', 'first_plan_recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('world_summaries');
    }
};
