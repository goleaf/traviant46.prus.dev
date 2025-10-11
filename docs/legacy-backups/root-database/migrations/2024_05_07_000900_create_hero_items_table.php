<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('type', 50);
            $table->text('description')->nullable();
            $table->json('attributes')->nullable();
            $table->timestamps();
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_items');
    }
};
