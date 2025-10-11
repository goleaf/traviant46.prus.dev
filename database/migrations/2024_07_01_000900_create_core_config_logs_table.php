<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_config_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setting_id')->constrained('core_config_settings');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action');
            $table->json('changes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_config_logs');
    }
};
