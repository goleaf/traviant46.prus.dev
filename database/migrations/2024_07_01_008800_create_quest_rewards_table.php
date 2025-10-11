<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quest_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quest_id')->constrained('quests');
            $table->string('reward_type');
            $table->unsignedInteger('quantity');
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_rewards');
    }
};
