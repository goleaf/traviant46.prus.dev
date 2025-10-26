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
        Schema::table('login_activities', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'login_activities_user_created_at_index');
            $table->index(['ip_address', 'user_id'], 'login_activities_ip_user_index');
            $table->index(['acting_sitter_id', 'created_at'], 'login_activities_sitter_created_at_index');
        });

        Schema::table('multi_account_alerts', function (Blueprint $table) {
            $table->index(['ip_address', 'last_seen_at'], 'multi_account_alerts_ip_last_seen_index');
            $table->index(['primary_user_id', 'last_seen_at'], 'multi_account_alerts_primary_last_seen_index');
            $table->index(['conflict_user_id', 'last_seen_at'], 'multi_account_alerts_conflict_last_seen_index');
        });

        Schema::table('sitter_delegations', function (Blueprint $table) {
            $table->index(['owner_user_id', 'updated_at'], 'sitter_delegations_owner_updated_index');
            $table->index(['sitter_user_id', 'updated_at'], 'sitter_delegations_sitter_updated_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('login_activities', function (Blueprint $table) {
            $table->dropIndex('login_activities_user_created_at_index');
            $table->dropIndex('login_activities_ip_user_index');
            $table->dropIndex('login_activities_sitter_created_at_index');
        });

        Schema::table('multi_account_alerts', function (Blueprint $table) {
            $table->dropIndex('multi_account_alerts_ip_last_seen_index');
            $table->dropIndex('multi_account_alerts_primary_last_seen_index');
            $table->dropIndex('multi_account_alerts_conflict_last_seen_index');
        });

        Schema::table('sitter_delegations', function (Blueprint $table) {
            $table->dropIndex('sitter_delegations_owner_updated_index');
            $table->dropIndex('sitter_delegations_sitter_updated_index');
        });
    }
};
