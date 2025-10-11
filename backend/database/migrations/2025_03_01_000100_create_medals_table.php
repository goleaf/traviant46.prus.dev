<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->unsignedTinyInteger('rank');
            $table->unsignedBigInteger('points')->default(0);
            $table->unsignedInteger('awarded_week');
            $table->json('metadata')->nullable();
            $table->timestamp('awarded_at');
            $table->timestamps();
            $table->unique(['user_id', 'category', 'awarded_week', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medals');
    }
};
