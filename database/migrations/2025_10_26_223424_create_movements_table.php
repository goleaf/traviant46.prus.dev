<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the movements table that tracks every in-flight troop or merchant journey.
     */
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('origin_village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('target_village_id')->constrained('villages')->cascadeOnDelete();
            $table->enum('type', ['attack', 'raid', 'reinforce', 'scout', 'return', 'merchant']);
            $table->json('payload');
            $table->timestamp('eta');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['eta', 'type']);
        });
    }

    /**
     * Drop the movements table when rolling back the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
