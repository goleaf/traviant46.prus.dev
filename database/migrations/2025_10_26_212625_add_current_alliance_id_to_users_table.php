<?php

declare(strict_types=1);

use App\Models\Alliance;
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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_alliance_id')) {
                $table->foreignIdFor(Alliance::class, 'current_alliance_id')
                    ->nullable()
                    ->after('role')
                    ->constrained('alliances')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_alliance_id')) {
                $table->dropConstrainedForeignId('current_alliance_id');
            }
        });
    }
};
