<?php

declare(strict_types=1);

use App\Models\Alliance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alliance_forums', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Alliance::class, 'alliance_id')->constrained('alliances')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('visible_to_sitters')->default(true);
            $table->boolean('moderators_only')->default(false);
            $table->timestamps();

            $table->unique(['alliance_id', 'name']);
            $table->index(['alliance_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alliance_forums');
    }
};
