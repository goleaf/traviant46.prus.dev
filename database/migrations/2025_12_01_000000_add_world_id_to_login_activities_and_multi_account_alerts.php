<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('login_activities', 'world_id')) {
                $table->string('world_id', 64)->nullable()->after('user_agent');
                $table->index('world_id');
            }
        });

        Schema::table('multi_account_alerts', function (Blueprint $table): void {
            if (! Schema::hasColumn('multi_account_alerts', 'world_id')) {
                $table->string('world_id', 64)->nullable()->after('source_type');
                $table->index(['world_id', 'last_seen_at'], 'multi_account_alerts_world_last_seen_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('login_activities', function (Blueprint $table): void {
            if (Schema::hasColumn('login_activities', 'world_id')) {
                $table->dropIndex('login_activities_world_id_index');
                $table->dropColumn('world_id');
            }
        });

        Schema::table('multi_account_alerts', function (Blueprint $table): void {
            if (Schema::hasColumn('multi_account_alerts', 'world_id')) {
                $table->dropIndex('multi_account_alerts_world_last_seen_index');
                $table->dropColumn('world_id');
            }
        });
    }
};
