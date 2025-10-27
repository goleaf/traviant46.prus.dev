<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the training queues table that powers troop production timers.
     */
    public function up(): void
    {
        Schema::create('training_queues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('troop_type_id')->constrained('troop_types');
            $table->unsignedInteger('count');
            $table->timestamp('finishes_at');
            $table->string('building_ref');
            $table->index('finishes_at');
        });
    }

    /**
     * Drop the training queues table when rolling back.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_queues');
    }
};
