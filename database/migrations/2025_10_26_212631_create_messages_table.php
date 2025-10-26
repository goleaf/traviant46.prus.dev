<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_message_id')->nullable()->unique();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('alliance_id')->nullable();
            $table->string('subject', 120);
            $table->text('body');
            $table->string('message_type', 32)->default('player');
            $table->string('delivery_scope', 32)->default('individual');
            $table->boolean('is_system_generated')->default(false);
            $table->boolean('is_broadcast')->default(false);
            $table->string('checksum', 32)->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable();
            $table->json('metadata')->nullable()->comment('Spam heuristics, sitter allowances, or alliance payload.');
            $table->timestamps();
            $table->index('sender_id');
            $table->index('alliance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
