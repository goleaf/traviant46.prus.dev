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
        if (! Schema::hasTable('villages')) {
            return;
        }

        if (! Schema::hasColumn('villages', 'user_id')) {
            Schema::table('villages', function (Blueprint $table): void {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('villages', 'owner_id')) {
            Schema::table('villages', static function (Blueprint $table): void {
                $table->dropForeign(['owner_id']);
            });

            DB::table('villages')
                ->whereNull('user_id')
                ->update(['user_id' => DB::raw('owner_id')]);

            Schema::table('villages', static function (Blueprint $table): void {
                $table->dropColumn('owner_id');
            });
        }

        Schema::table('villages', function (Blueprint $table): void {
            if (! Schema::hasColumn('villages', 'legacy_kid')) {
                $table->unsignedInteger('legacy_kid')
                    ->nullable()
                    ->after('id')
                    ->comment('Original Travian map identifier (kid).')
                    ->unique();
            }

            if (! Schema::hasColumn('villages', 'alliance_id')) {
                $table->unsignedBigInteger('alliance_id')
                    ->nullable()
                    ->after('user_id');
                $table->index('alliance_id');
            }

            if (! Schema::hasColumn('villages', 'loyalty')) {
                $table->unsignedTinyInteger('loyalty')
                    ->default(100)
                    ->after('population');
            }

            if (! Schema::hasColumn('villages', 'culture_points')) {
                $table->unsignedInteger('culture_points')
                    ->default(0)
                    ->after('loyalty');
            }

            if (! Schema::hasColumn('villages', 'terrain_type')) {
                $table->unsignedTinyInteger('terrain_type')
                    ->default(1)
                    ->after('y_coordinate')
                    ->comment('Legacy fieldtype descriptor.');
            }

            if (! Schema::hasColumn('villages', 'village_category')) {
                $table->string('village_category', 32)
                    ->nullable()
                    ->after('terrain_type')
                    ->comment('Legacy Travian type (normal, wonder, natar, oasis hub).');
            }

            if (! Schema::hasColumn('villages', 'resource_balances')) {
                $table->json('resource_balances')
                    ->nullable()
                    ->after('is_capital');
            }

            if (! Schema::hasColumn('villages', 'storage')) {
                $table->json('storage')
                    ->nullable()
                    ->after('resource_balances');
            }

            if (! Schema::hasColumn('villages', 'production')) {
                $table->json('production')
                    ->nullable()
                    ->after('storage');
            }

            if (! Schema::hasColumn('villages', 'defense_bonus')) {
                $table->json('defense_bonus')
                    ->nullable()
                    ->after('production');
            }

            if (! Schema::hasColumn('villages', 'founded_at')) {
                $table->timestamp('founded_at')
                    ->nullable()
                    ->after('is_capital');
            }

            if (! Schema::hasColumn('villages', 'abandoned_at')) {
                $table->timestamp('abandoned_at')
                    ->nullable()
                    ->after('founded_at');
            }

            if (! Schema::hasColumn('villages', 'last_loyalty_change_at')) {
                $table->timestamp('last_loyalty_change_at')
                    ->nullable()
                    ->after('abandoned_at');
            }

            if (! Schema::hasColumn('villages', 'watcher_user_id')) {
                $table->foreignId('watcher_user_id')
                    ->nullable()
                    ->after('last_loyalty_change_at')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->comment('Legacy checker field tracking sitter oversight.');
            }

            if (! Schema::hasColumn('villages', 'is_wonder_village')) {
                $table->boolean('is_wonder_village')
                    ->default(false)
                    ->after('watcher_user_id');
            }

            if (! Schema::hasColumn('villages', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('villages')) {
            return;
        }

        Schema::table('villages', function (Blueprint $table): void {
            if (Schema::hasColumn('villages', 'watcher_user_id')) {
                $table->dropForeign(['watcher_user_id']);
                $table->dropColumn('watcher_user_id');
            }

            if (Schema::hasColumn('villages', 'alliance_id')) {
                $table->dropIndex('villages_alliance_id_index');
                $table->dropColumn('alliance_id');
            }

            if (Schema::hasColumn('villages', 'legacy_kid')) {
                $table->dropUnique('villages_legacy_kid_unique');
                $table->dropColumn('legacy_kid');
            }

            foreach ([
                'loyalty',
                'culture_points',
                'terrain_type',
                'village_category',
                'resource_balances',
                'storage',
                'production',
                'defense_bonus',
                'abandoned_at',
                'last_loyalty_change_at',
                'is_wonder_village',
            ] as $column) {
                if (Schema::hasColumn('villages', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('villages', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        if (! Schema::hasColumn('villages', 'owner_id')) {
            Schema::table('villages', function (Blueprint $table): void {
                $table->foreignId('owner_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });

            DB::table('villages')
                ->whereNull('owner_id')
                ->update(['owner_id' => DB::raw('user_id')]);
        }

        if (Schema::hasColumn('villages', 'user_id')) {
            Schema::table('villages', static function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
