<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->enum('resource_type', ['wood', 'clay', 'iron', 'crop']);
            $table->unsignedTinyInteger('level')->default(0);
            $table->unsignedInteger('production_per_hour')->default(0);
            $table->unsignedInteger('storage_capacity')->default(0);
            $table->json('bonuses')->nullable();
            $table->timestamps();
            $table->unique(['village_id', 'resource_type']);
            $table->index(['resource_type', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_resources');
    }
};
