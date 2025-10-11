<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdata_message_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('mdata_messages');
            $table->foreignId('archived_by')->constrained('users');
            $table->dateTime('archived_at');
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdata_message_archives');
    }
};
