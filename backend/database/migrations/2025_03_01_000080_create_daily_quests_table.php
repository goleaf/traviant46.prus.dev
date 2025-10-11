<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_quests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('quest_key');
            $table->json('progress')->nullable();
            $table->unsignedSmallInteger('current_step')->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reward_claimed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'quest_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_quests');
    }
};
