<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_building_upgrades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('village_building_id')->nullable()->constrained('village_buildings')->nullOnDelete();
            $table->foreignId('building_type_id')->constrained('building_types')->cascadeOnDelete();
            $table->unsignedTinyInteger('current_level')->default(0);
            $table->unsignedTinyInteger('target_level');
            $table->unsignedTinyInteger('queue_position')->default(0);
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('completes_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'completes_at']);
            $table->index(['village_id', 'building_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_building_upgrades');
    }
};
