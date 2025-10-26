<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_number');
            $table->enum('kind', ['wood', 'clay', 'iron', 'crop']);
            $table->unsignedTinyInteger('level')->default(0);
            $table->unsignedInteger('production_per_hour_cached')->default(0);
            $table->timestamps();

            $table->unique(['village_id', 'slot_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_fields');
    }
};
