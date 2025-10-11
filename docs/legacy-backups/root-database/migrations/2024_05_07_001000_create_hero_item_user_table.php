<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_item_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hero_id')->constrained('heroes')->cascadeOnDelete();
            $table->foreignId('hero_item_id')->constrained('hero_items')->cascadeOnDelete();
            $table->string('slot', 50)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['hero_id', 'hero_item_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_item_user');
    }
};
