<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @internal This migration defines the primary metadata table for Travian game worlds.
 */
return new class extends Migration
{
    /**
     * Create the `worlds` table which stores meta attributes for each game world.
     */
    public function up(): void
    {
        Schema::create('worlds', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedDecimal('speed', 4, 2)->default(1.00);
            $table->json('features')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Drop the `worlds` table when rolling the migration back.
     */
    public function down(): void
    {
        Schema::dropIfExists('worlds');
    }
};
