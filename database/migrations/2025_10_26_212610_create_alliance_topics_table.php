<?php

declare(strict_types=1);

use App\Models\Alliance;
use App\Models\AllianceForum;
use App\Models\User;
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
        Schema::create('alliance_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AllianceForum::class, 'forum_id')->constrained('alliance_forums')->cascadeOnDelete();
            $table->foreignIdFor(Alliance::class, 'alliance_id')->constrained('alliances')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'acting_sitter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('last_posted_at')->nullable();
            $table->timestamps();

            $table->index(['forum_id', 'is_pinned', 'last_posted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alliance_topics');
    }
};
