<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('building_catalog', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('building_type_id')
                ->unique()
                ->nullable()
                ->constrained('building_types')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('building_slug')->unique();
            $table->string('name');
            $table->json('prerequisites')->nullable();
            $table->json('bonuses_per_level')->nullable();
            $table->json('storage_capacity_per_level')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building_catalog');
    }
};
