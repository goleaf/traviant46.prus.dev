<?php

declare(strict_types=1);

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
            $table->string('source_type', 32)->default('ip')->after('group_key');
            $table->string('device_hash', 64)->nullable()->after('source_type');
            $table->unsignedInteger('occurrences')->default(1)->after('user_ids');
            $table->timestamp('window_started_at')->nullable()->after('occurrences');
            $table->string('status', 32)->default('open')->after('severity');
            $table->string('suppression_reason', 255)->nullable()->after('status');
            $table->timestamp('resolved_at')->nullable()->after('suppression_reason');
            $table->foreignId('resolved_by_user_id')->nullable()->after('resolved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('dismissed_at')->nullable()->after('resolved_by_user_id');
            $table->foreignId('dismissed_by_user_id')->nullable()->after('dismissed_at')->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable()->after('dismissed_by_user_id');
            $table->json('metadata')->nullable()->after('notes');
            $table->timestamp('last_notified_at')->nullable()->after('metadata');
            $table->index('device_hash');
            $table->index('status');
        });

        DB::table('multi_account_alerts')->update([
            'source_type' => 'ip',
            'status' => 'open',
            'occurrences' => 1,
            'window_started_at' => DB::raw('first_seen_at'),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('multi_account_alerts', 'primary_user_id')) {
            return;
        }

        Schema::table('multi_account_alerts', function (Blueprint $table) {
            $table->dropForeign(['resolved_by_user_id']);
            $table->dropForeign(['dismissed_by_user_id']);
            $table->dropIndex(['device_hash']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'source_type',
                'device_hash',
                'occurrences',
                'window_started_at',
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
