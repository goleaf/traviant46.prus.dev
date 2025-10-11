<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('profile_name');
            $table->text('biography')->nullable();
            $table->json('settings')->nullable();
            $table->unique(['user_id', 'profile_name']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_user_profiles');
    }
};
