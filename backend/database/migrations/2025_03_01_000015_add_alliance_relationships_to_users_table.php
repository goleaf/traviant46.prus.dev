<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_alliance_id')) {
                $table->foreignId('current_alliance_id')
                    ->nullable()
                    ->after('sit2_uid');
            }

            $table->foreign('current_alliance_id')
                ->references('id')
                ->on('alliances')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_alliance_id')) {
                $table->dropForeign(['current_alliance_id']);
            }
        });
    }
};
