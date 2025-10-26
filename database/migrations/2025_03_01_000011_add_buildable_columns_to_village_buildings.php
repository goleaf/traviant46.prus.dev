<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('village_buildings', function (Blueprint $table): void {
            $table->string('buildable_type')->nullable()->after('building_type');
            $table->unsignedBigInteger('buildable_id')->nullable()->after('buildable_type');
            $table->index(['buildable_type', 'buildable_id'], 'village_buildings_buildable_index');
        });
    }

    public function down(): void
    {
        Schema::table('village_buildings', function (Blueprint $table): void {
            $table->dropIndex('village_buildings_buildable_index');
            $table->dropColumn(['buildable_id', 'buildable_type']);
        });
    }
};
