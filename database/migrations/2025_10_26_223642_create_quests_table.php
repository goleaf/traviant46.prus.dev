<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create the quests catalog used by the quest log service.
     */
    public function up(): void
    {
        Schema::create('quests', function (Blueprint $table): void {
            $table->id();
            $table->string('quest_code', 64)
                ->unique()
                ->comment('Stable quest identifier that matches config-driven definitions.');
            $table->string('title', 128)
                ->comment('Player-facing quest title rendered in the quest log.');
            $table->text('description')
                ->comment('Detailed quest description outlining objectives and lore.');
            $table->boolean('is_repeatable')
                ->default(false)
                ->comment('Marks daily quests that refresh on schedule.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations and drop the quests catalog.
     */
    public function down(): void
    {
        Schema::dropIfExists('quests');
    }
};
