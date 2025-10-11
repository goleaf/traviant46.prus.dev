<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infoboxes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scope', 30)->default('global')->index();
            $table->string('category', 50)->index();
            $table->string('title');
            $table->text('body');
            $table->json('parameters')->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('is_system_generated')->default(false);
            $table->timestamps();
        });

        Schema::create('infobox_user_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('infobox_id')->constrained('infoboxes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['infobox_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infobox_user_states');
        Schema::dropIfExists('infoboxes');
    }
};
