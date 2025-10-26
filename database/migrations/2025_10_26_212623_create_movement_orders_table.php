<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_movement_id')->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('origin_village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('target_village_id')->constrained('villages')->cascadeOnDelete();
            $table->string('movement_type', 32);
            $table->string('mission', 32)->nullable();
            $table->string('status', 24)->default('pending');
            $table->string('checksum', 40)->nullable();
            $table->timestamp('depart_at')->nullable()->index();
            $table->timestamp('arrive_at')->nullable()->index();
            $table->timestamp('return_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->json('payload')->nullable()->comment('Unit and siege payload keyed by unit identifier.');
            $table->json('metadata')->nullable()->comment('Hero redeploy toggles, spy targets, or rally point flags.');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'movement_type'], 'movement_orders_status_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_orders');
    }
};
