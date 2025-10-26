<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('multi_account_alerts', function (Blueprint $table) {
            $table->id();
            $table->uuid('alert_id')->unique();
            $table->string('group_key', 128)->unique();
            $table->string('source_type', 20);
            $table->string('ip_address', 45)->nullable();
            $table->string('ip_address_hash', 128)->nullable();
            $table->string('device_hash', 64)->nullable();
            $table->json('user_ids');
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('window_started_at')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->string('severity', 32)->default('low');
            $table->string('status', 20)->default('open');
            $table->string('suppression_reason', 255)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dismissed_at')->nullable();
            $table->foreignId('dismissed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->index('source_type');
            $table->index('ip_address');
            $table->index('ip_address_hash');
            $table->index('device_hash');
            $table->index('last_seen_at');
            $table->index('severity');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multi_account_alerts');
    }
};
