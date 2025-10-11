<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_topics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('alliance_id')->nullable()->constrained('alliances')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category', 50)->nullable()->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->boolean('is_locked')->default(false)->index();
            $table->boolean('is_pinned')->default(false)->index();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('posts_count')->default(0);
            $table->timestamp('last_posted_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('forum_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('topic_id')->constrained('forum_topics')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('edited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('content');
            $table->timestamp('edited_at')->nullable();
            $table->boolean('is_deleted')->default(false)->index();
            $table->timestamps();

            $table->index(['topic_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
        Schema::dropIfExists('forum_topics');
    }
};
