<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->unsignedSmallInteger('building_type');
            $table->unsignedTinyInteger('position');
            $table->unsignedTinyInteger('level')->default(0);
            $table->index(['village_id', 'building_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
