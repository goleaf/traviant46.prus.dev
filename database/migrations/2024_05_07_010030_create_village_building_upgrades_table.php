<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_building_upgrades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('village_building_id')->nullable()->constrained('village_buildings')->nullOnDelete();
            $table->foreignId('building_type_id')->nullable()->constrained('building_types')->nullOnDelete();
            $table->unsignedTinyInteger('building_slot');
            $table->unsignedTinyInteger('current_level')->default(0);
            $table->unsignedTinyInteger('target_level');
            $table->boolean('uses_master_builder')->default(false);
            $table->enum('status', ['queued', 'in_progress', 'completed', 'cancelled'])->default('queued')->index();
            $table->timestamp('queued_at')->useCurrent()->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('finishes_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['village_id', 'building_slot', 'status'], 'village_building_upgrades_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_building_upgrades');
    }
};
