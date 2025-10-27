<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the build_queues table used to persist queued building upgrades.
 */
return new class extends Migration
{
    /**
     * Run the migrations to install the build queue schedule.
     */
    public function up(): void
    {
        Schema::create('build_queues', function (Blueprint $table): void {
            // Primary identifier for each queued build order.
            $table->id();

            // Reference to the owning village so queued items can be resolved quickly.
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();

            // Legacy Travian building type identifier (gid) that the queue item targets.
            $table->unsignedSmallInteger('building_type');

            // Desired level to achieve once the queue item completes.
            $table->unsignedTinyInteger('target_level');

            // Scheduled completion timestamp used by queue workers.
            $table->timestamp('finishes_at');

            // Processing state to coordinate queue workers.
            $table->enum('state', ['pending', 'working', 'done'])->default('pending');

            $table->timestamps();
            $table->index('finishes_at');
        });
    }

    /**
     * Reverse the build queue migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('build_queues');
    }
};
