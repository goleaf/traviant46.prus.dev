<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('preference_key');
            $table->text('preference_value')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->unique(['user_id', 'preference_key']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_user_preferences');
    }
};
