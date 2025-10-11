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
            $table->decimal('current_stock', 20, 4)->default(0);
            $table->decimal('storage_capacity', 20, 4)->default(0);
            $table->decimal('production_per_hour', 20, 4)->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->json('bonuses')->nullable();
            $table->timestamps();

            $table->unique(['village_id', 'resource_type']);
            $table->index(['resource_type', 'last_calculated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_resources');
    }
};
