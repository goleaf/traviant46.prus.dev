<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_post_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('forum_posts');
            $table->foreignId('editor_id')->nullable()->constrained('users');
            $table->text('content');
            $table->dateTime('edited_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_post_revisions');
    }
};
