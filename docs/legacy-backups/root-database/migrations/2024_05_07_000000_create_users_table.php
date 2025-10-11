<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 50);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->unsignedTinyInteger('race')->nullable()->index();
            $table->unsignedTinyInteger('access_level')->default(1)->index();
            $table->unsignedInteger('gold_balance')->default(0);
            $table->unsignedInteger('silver_balance')->default(0);
            $table->unsignedInteger('victory_points')->default(0);
            $table->boolean('is_banned')->default(false)->index();
            $table->json('preferences')->nullable();
            $table->json('statistics')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('last_login_at')->nullable()->index();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
            $table->index('name');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
