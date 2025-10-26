<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('recipient_alliance_id')->nullable();
            $table->string('status', 24)->default('unread');
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_muted')->default(false);
            $table->boolean('is_reported')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->json('flags')->nullable()->comment('Legacy flags (autoType, delete_receiver/delete_sender) retained for audits.');
            $table->timestamps();
            $table->unique(['message_id', 'recipient_id']);
            $table->index(['recipient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_recipients');
    }
};
