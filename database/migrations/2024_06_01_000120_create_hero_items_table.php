<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hero_items');
    }

    public function down(): void
    {
        Schema::create('hero_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('hero_id')->nullable();
            $table->string('slot', 40);
            $table->string('type', 60);
            $table->string('rarity', 20)->default('common');
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('is_equipped')->default(false);
            $table->json('attributes')->nullable();
            $table->timestamp('acquired_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'slot']);
            $table->index(['hero_id', 'is_equipped']);
        });
    }
};
