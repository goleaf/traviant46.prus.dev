<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('device_identifier');
            $table->string('device_type', 32);
            $table->timestamp('last_seen_at')->nullable();
            $table->unique(['user_id', 'device_identifier']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_user_devices');
    }
};
