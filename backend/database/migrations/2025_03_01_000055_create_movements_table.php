<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('origin_village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('target_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->enum('movement_type', ['reinforcement', 'attack', 'raid', 'scout', 'trade', 'return'])->index();
            $table->enum('status', ['queued', 'en_route', 'arrived', 'cancelled', 'completed'])->default('queued')->index();
            $table->timestamp('depart_at')->nullable()->index();
            $table->timestamp('arrive_at')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
