<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_statistics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('weekly_attack_points')->default(0);
            $table->unsignedBigInteger('weekly_defense_points')->default(0);
            $table->unsignedBigInteger('weekly_robber_points')->default(0);
            $table->unsignedBigInteger('weekly_climber_points')->default(0);
            $table->unsignedBigInteger('population')->default(0);
            $table->timestamps();
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_statistics');
    }
};
