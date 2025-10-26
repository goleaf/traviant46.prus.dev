<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('multi_account_alerts', 'primary_user_id')) {
            return;
        }

        Schema::table('multi_account_alerts', function (Blueprint $table) {
            $table->dropUnique('multi_account_unique');
            $table->string('source_type', 20)->default('ip')->after('id');
            $table->string('identifier', 255)->after('source_type');
            $table->string('device_hash', 64)->nullable()->after('identifier');
            $table->unsignedSmallInteger('unique_user_count')->default(2)->after('occurrences');
            $table->timestamp('window_started_at')->nullable()->after('unique_user_count');
            $table->string('severity', 20)->default('low')->after('last_seen_at');
            $table->string('status', 20)->default('open')->after('severity');
            $table->string('suppression_reason', 255)->nullable()->after('status');
            $table->timestamp('resolved_at')->nullable()->after('suppression_reason');
            $table->foreignId('resolved_by_user_id')->nullable()->after('resolved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('dismissed_at')->nullable()->after('resolved_by_user_id');
            $table->foreignId('dismissed_by_user_id')->nullable()->after('dismissed_at')->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable()->after('dismissed_by_user_id');
            $table->json('metadata')->nullable()->after('notes');
            $table->timestamp('last_notified_at')->nullable()->after('metadata');
            $table->unique(['source_type', 'identifier', 'primary_user_id', 'conflict_user_id'], 'multi_account_identifier_unique');
        });

        DB::table('multi_account_alerts')->update([
            'source_type' => 'ip',
            'identifier' => DB::raw('ip_address'),
            'status' => 'open',
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('multi_account_alerts', 'primary_user_id')) {
            return;
        }

        Schema::table('multi_account_alerts', function (Blueprint $table) {
            $table->dropUnique('multi_account_identifier_unique');
            $table->dropForeign(['resolved_by_user_id']);
            $table->dropForeign(['dismissed_by_user_id']);
            $table->dropColumn([
                'source_type',
                'identifier',
                'device_hash',
                'unique_user_count',
                'window_started_at',
                'severity',
                'status',
                'suppression_reason',
                'resolved_at',
                'resolved_by_user_id',
                'dismissed_at',
                'dismissed_by_user_id',
                'notes',
                'metadata',
                'last_notified_at',
            ]);
            $table->unique(['ip_address', 'primary_user_id', 'conflict_user_id'], 'multi_account_unique');
        });
    }
};
