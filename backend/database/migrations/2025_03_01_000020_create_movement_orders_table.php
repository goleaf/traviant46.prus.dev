<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('origin_village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('target_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->enum('movement_type', ['trade', 'return', 'reinforcement', 'attack', 'raid', 'scout'])->default('trade');
            $table->enum('status', ['pending', 'en_route', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('depart_at')->nullable();
            $table->timestamp('arrive_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['movement_type', 'status']);
            $table->index('arrive_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_orders');
    }
};
