<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('target_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->foreignId('movement_id')->nullable()->constrained('movements')->nullOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('checksum', 12);
            $table->enum('attack_type', ['reinforcement', 'raid', 'attack', 'scout', 'settle'])->default('attack');
            $table->boolean('redeploy_hero')->default(false);
            $table->json('unit_counts');
            $table->timestamp('queued_at')->useCurrent();
            $table->timestamps();

            $table->index(['scheduled_at', 'attack_type']);
            $table->unique(['scheduled_at', 'checksum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attacks');
    }
};
