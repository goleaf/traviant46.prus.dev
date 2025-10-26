<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reinforcement_garrisons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_enforcement_id')->nullable()->unique();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('home_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->foreignId('stationed_village_id')->constrained('villages')->cascadeOnDelete();
            $table->unsignedBigInteger('supporting_alliance_id')->nullable();
            $table->json('unit_composition')->nullable()->comment('Per-unit counts keyed by unit identifier.');
            $table->unsignedInteger('upkeep')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('deployed_at')->nullable()->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('metadata')->nullable()->comment('Artifacts, morale bonuses, and source tribe identifiers.');
            $table->timestamps();
            $table->index(['stationed_village_id', 'is_active'], 'reinforcement_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reinforcement_garrisons');
    }
};
