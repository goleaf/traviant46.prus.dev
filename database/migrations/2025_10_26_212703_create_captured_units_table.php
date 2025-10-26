<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captured_units', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_trapped_id')->nullable()->unique();
            $table->foreignId('captor_village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('source_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('unit_composition')->nullable()->comment('Captured troop stack keyed by unit identifier.');
            $table->string('status', 24)->default('captured');
            $table->timestamp('captured_at')->nullable()->index();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->json('metadata')->nullable()->comment('Trap capacity, release timers, and hero status.');
            $table->timestamps();
            $table->index(['captor_village_id', 'status'], 'captured_units_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captured_units');
    }
};
