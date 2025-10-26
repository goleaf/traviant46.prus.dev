<?php

declare(strict_types=1);

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
        Schema::table('login_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('login_activities', 'ip_address_hash')) {
                $table->string('ip_address_hash', 128)->nullable()->after('ip_address');
                $table->index('ip_address_hash');
            }
        });

        Schema::table('login_ip_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('login_ip_logs', 'ip_address_hash')) {
                $table->string('ip_address_hash', 128)->nullable()->after('ip_address');
                $table->index('ip_address_hash');
            }
        });

        Schema::table('multi_account_alerts', function (Blueprint $table): void {
            if (! Schema::hasColumn('multi_account_alerts', 'ip_address_hash')) {
                $table->string('ip_address_hash', 128)->nullable()->after('ip_address');
                $table->index('ip_address_hash');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'last_login_ip_hash')) {
                $table->string('last_login_ip_hash', 128)->nullable()->after('last_login_ip');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('login_activities', function (Blueprint $table): void {
            if (Schema::hasColumn('login_activities', 'ip_address_hash')) {
                $table->dropIndex(['ip_address_hash']);
                $table->dropColumn('ip_address_hash');
            }
        });

        Schema::table('login_ip_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('login_ip_logs', 'ip_address_hash')) {
                $table->dropIndex(['ip_address_hash']);
                $table->dropColumn('ip_address_hash');
            }
        });

        Schema::table('multi_account_alerts', function (Blueprint $table): void {
            if (Schema::hasColumn('multi_account_alerts', 'ip_address_hash')) {
                $table->dropIndex(['ip_address_hash']);
                $table->dropColumn('ip_address_hash');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'last_login_ip_hash')) {
                $table->dropColumn('last_login_ip_hash');
            }
        });
    }
};
