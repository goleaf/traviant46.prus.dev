<?php

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
            $table->string('ip_address', 45)->nullable();
            $table->json('user_ids');
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->string('severity', 32)->default('low');
            $table->timestamps();
            $table->index('ip_address');
            $table->index('last_seen_at');
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multi_account_alerts');
    }
};
