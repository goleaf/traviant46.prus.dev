<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_queue', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 30)->default('in_app')->index();
            $table->string('template', 100);
            $table->json('payload')->nullable();
            $table->timestamp('scheduled_at')->useCurrent()->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->enum('status', ['pending', 'processing', 'sent', 'failed'])->default('pending')->index();
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_queue');
    }
};
