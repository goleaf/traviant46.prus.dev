<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_logistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('vdata_villages');
            $table->unsignedInteger('warehouse_capacity')->default(0);
            $table->unsignedInteger('granary_capacity')->default(0);
            $table->json('trade_routes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_logistics');
    }
};
