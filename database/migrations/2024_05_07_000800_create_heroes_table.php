<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero', function (Blueprint $table): void {
            $table->unsignedInteger('uid')->primary();
            $table->unsignedInteger('kid');
            $table->unsignedBigInteger('exp')->default(0);
            $table->decimal('health', 13, 10)->unsigned()->default('100.0000000000');
            $table->unsignedInteger('itemHealth')->default(0);
            $table->unsignedSmallInteger('power')->default(0);
            $table->unsignedSmallInteger('offBonus')->default(0);
            $table->unsignedSmallInteger('defBonus')->default(0);
            $table->unsignedSmallInteger('production')->default(4);
            $table->unsignedTinyInteger('productionType')->default(0);
            $table->unsignedInteger('lastupdate')->default(0);
            $table->unsignedTinyInteger('hide')->default(1);

            $table->index('health');
            $table->index('lastupdate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero');
    }
};
