<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdata_message_threads', function (Blueprint $table) {
            $table->id();
            $table->string('thread_key')->unique();
            $table->string('topic');
            $table->foreignId('starter_id')->constrained('users');
            $table->dateTime('last_message_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdata_message_threads');
    }
};
