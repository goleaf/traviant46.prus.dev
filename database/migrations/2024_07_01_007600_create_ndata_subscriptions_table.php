<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ndata_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('channel_id')->constrained('ndata_notification_channels');
            $table->json('preferences')->nullable();
            $table->dateTime('subscribed_at');
            $table->unique(['user_id', 'channel_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ndata_subscriptions');
    }
};
