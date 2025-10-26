<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quest_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quest_id')->constrained('quests')->cascadeOnDelete();
            $table->string('state', 32)->default('pending');
            $table->json('progress')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'quest_id']);
            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_progress');
    }
};
