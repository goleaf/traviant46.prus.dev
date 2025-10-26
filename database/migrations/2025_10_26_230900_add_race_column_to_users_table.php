<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'race')) {
                $position = Schema::hasColumn('users', 'role') ? 'role' : 'password';

                $table->unsignedTinyInteger('race')
                    ->nullable()
                    ->after($position)
                    ->comment('Legacy tribe identifier used for gameplay logic.');
            }

            if (! Schema::hasColumn('users', 'tribe')) {
                $table->unsignedTinyInteger('tribe')
                    ->nullable()
                    ->after('race')
                    ->comment('Optional alias for tribe, kept for backwards compatibility.');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'tribe')) {
                $table->dropColumn('tribe');
            }

            if (Schema::hasColumn('users', 'race')) {
                $table->dropColumn('race');
            }
        });
    }
};
