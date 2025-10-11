<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdata_message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('mdata_messages');
            $table->foreignId('recipient_id')->constrained('users');
            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();
            $table->unique(['message_id', 'recipient_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdata_message_recipients');
    }
};
