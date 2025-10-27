<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the buildings table that records every constructed structure within a village.
     */
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')
                ->constrained('villages')
                ->cascadeOnDelete()
                ->comment('Village that owns the building record.');
            $table->unsignedSmallInteger('building_type')
                ->comment('Identifier that maps to the building_types catalog.');
            $table->unsignedTinyInteger('position')
                ->comment('Slot position on the village layout where the building resides.');
            $table->unsignedTinyInteger('level')
                ->default(0)
                ->comment('Current upgrade level of the building.');
            $table->index(['village_id', 'building_type']);
        });
    }

    /**
     * Drop the buildings table if the migration is rolled back.
     */
    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
