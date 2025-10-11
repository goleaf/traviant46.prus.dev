<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attack_dispatches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('origin_village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('target_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->string('dispatch_token', 12)->nullable()->comment('Legacy timestamp checksum for compatibility');
            $table->enum('attack_type', [
                'raid',
                'assault',
                'siege',
                'scout',
                'reinforcement',
                'adventure',
            ])->default('assault')->index();
            $table->enum('status', ['queued', 'dispatched', 'arrived', 'cancelled'])->default('queued')->index();
            $table->boolean('includes_hero')->default(false);
            $table->boolean('redeploy_hero')->default(false);
            $table->timestamp('queued_at')->useCurrent()->index();
            $table->timestamp('dispatched_at')->nullable()->index();
            $table->timestamp('arrives_at')->nullable()->index();
            $table->json('metadata')->nullable()->comment('Additional payload copied from the legacy queue');
            $table->timestamps();

            $table->index(['origin_village_id', 'target_village_id'], 'attack_dispatches_origin_target_idx');
        });

        Schema::create('attack_dispatch_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attack_dispatch_id')->constrained('attack_dispatches')->cascadeOnDelete();
            $table->foreignId('unit_stat_id')->constrained('unit_stats')->cascadeOnDelete();
            $table->unsignedBigInteger('quantity')->default(0);
            $table->boolean('is_hero')->default(false);
            $table->timestamps();

            $table->unique(['attack_dispatch_id', 'unit_stat_id'], 'attack_dispatch_units_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attack_dispatch_units');
        Schema::dropIfExists('attack_dispatches');
    }
};
