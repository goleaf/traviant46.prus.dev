<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heroes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('village_id')->nullable();
            $table->unsignedBigInteger('home_village_id')->nullable();
            $table->string('name', 75);
            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedBigInteger('experience')->default(0);
            $table->unsignedTinyInteger('health')->default(100);
            $table->unsignedTinyInteger('energy')->default(100);
            $table->enum('status', ['idle', 'moving', 'adventuring', 'training', 'healing', 'dead'])->default('idle');
            $table->json('attributes')->nullable();
            $table->json('equipment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_moved_at')->nullable();
            $table->timestamp('last_revived_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heroes');
    }
};
