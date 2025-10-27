<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to persist per-user quest progress payloads.
     */
    public function up(): void
    {
        Schema::create('quest_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Player progressing through the quest.');
            $table->foreignId('quest_id')
                ->constrained('quests')
                ->cascadeOnDelete()
                ->comment('Quest definition reference.');
            $table->string('state', 32)
                ->default('pending')
                ->comment('Progress state such as pending or completed.');
            $table->json('progress')
                ->nullable()
                ->comment('Structured objective/reward payload tracked as JSON.');
            $table->timestamps();

            $table->unique(['user_id', 'quest_id']);
            $table->index('state');
        });
    }

    /**
     * Reverse the migrations by dropping quest progress records.
     */
    public function down(): void
    {
        Schema::dropIfExists('quest_progress');
    }
};
