<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedInteger('legacy_uid')->nullable()->unique();
            $table->string('username')->unique();
            $table->string('name', 50);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->foreignIdFor(User::class, 'sit1_uid')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'sit2_uid')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_alliance_id')->nullable();
            $table->unsignedTinyInteger('race')->nullable()->index();
            $table->unsignedTinyInteger('access_level')->default(1)->index();
            $table->unsignedInteger('gold_balance')->default(0);
            $table->unsignedInteger('silver_balance')->default(0);
            $table->unsignedInteger('victory_points')->default(0);
            $table->boolean('is_banned')->default(false)->index();
            $table->json('preferences')->nullable();
            $table->json('statistics')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('last_owner_login_at')->nullable();
            $table->timestamp('last_login_at')->nullable()->index();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->ipAddress('last_login_ip')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->index('name');
            $table->index('created_at');
            $table->index('updated_at');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
