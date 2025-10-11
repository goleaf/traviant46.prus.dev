<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('building_type_id')->nullable()->constrained('building_types')->nullOnDelete();
            $table->unsignedTinyInteger('slot');
            $table->unsignedTinyInteger('level')->default(0);
            $table->boolean('is_under_construction')->default(false);
            $table->json('queue')->nullable();
            $table->json('attributes')->nullable();
            $table->timestamps();
            $table->unique(['village_id', 'slot']);
            $table->index(['building_type_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_buildings');
    }
};
