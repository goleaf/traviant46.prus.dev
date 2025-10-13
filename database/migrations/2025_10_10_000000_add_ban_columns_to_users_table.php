<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_banned')->default(false)->after('two_factor_confirmed_at');
            $table->text('ban_reason')->nullable()->after('is_banned');
            $table->timestamp('ban_issued_at')->nullable()->after('ban_reason');
            $table->timestamp('ban_expires_at')->nullable()->after('ban_issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_banned',
                'ban_reason',
                'ban_issued_at',
                'ban_expires_at',
            ]);
        });
    }
};
