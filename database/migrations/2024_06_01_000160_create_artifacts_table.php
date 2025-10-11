<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifacts', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('type', 50)->index();
            $table->enum('effect_scope', ['village', 'account', 'global'])->index();
            $table->json('effects')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('owner_alliance_id')->nullable()->constrained('alliances')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('spawn_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->timestamp('captured_at')->nullable()->index();
            $table->timestamp('cooldown_ends_at')->nullable()->index();
            $table->timestamps();

            $table->index(['type', 'effect_scope']);
            $table->index(['owner_alliance_id', 'owner_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifacts');
    }
};
