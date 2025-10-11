<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_order_id')->constrained('movement_orders')->cascadeOnDelete();
            $table->foreignId('unit_stat_id')->constrained('unit_stats')->cascadeOnDelete();
            $table->unsignedBigInteger('quantity')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['movement_order_id', 'unit_stat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_units');
    }
};
