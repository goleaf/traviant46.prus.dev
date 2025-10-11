<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alliances', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->json('bonus_levels')->nullable();
            $table->timestamps();
        });

        Schema::create('alliance_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('alliance_id')->constrained('alliances')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('contribution')->default(0);
            $table->timestamps();
            $table->unique(['alliance_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alliance_members');
        Schema::dropIfExists('alliances');
    }
};
