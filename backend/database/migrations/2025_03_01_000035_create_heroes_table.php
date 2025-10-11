<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heroes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('current_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->foreignId('home_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->string('name', 75);
            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedBigInteger('experience')->default(0);
            $table->unsignedTinyInteger('health')->default(100);
            $table->unsignedTinyInteger('energy')->default(100);
            $table->unsignedTinyInteger('loyalty')->default(100);
            $table->enum('status', ['idle', 'moving', 'adventuring', 'training', 'healing', 'dead'])->default('idle');
            $table->json('attributes')->nullable();
            $table->json('skills')->nullable();
            $table->json('appearance')->nullable();
            $table->json('equipment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_moved_at')->nullable();
            $table->timestamp('last_revived_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['current_village_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heroes');
    }
};
