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
            $table->string('name', 50);
            $table->unsignedTinyInteger('level')->default(0);
            $table->unsignedInteger('experience')->default(0);
            $table->unsignedTinyInteger('health')->default(100);
            $table->unsignedInteger('loyalty')->default(100);
            $table->json('attributes')->nullable();
            $table->json('skills')->nullable();
            $table->json('appearance')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heroes');
    }
};
