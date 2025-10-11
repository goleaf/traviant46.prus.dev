<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hero_item_user');
    }

    public function down(): void
    {
        Schema::create('hero_item_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('hero_id');
            $table->unsignedBigInteger('hero_item_id');
            $table->string('slot', 50)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['hero_id', 'hero_item_id', 'slot']);
        });
    }
};
