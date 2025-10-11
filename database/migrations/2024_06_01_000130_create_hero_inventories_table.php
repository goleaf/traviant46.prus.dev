<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table): void {
            $table->unsignedInteger('uid')->primary();
            $table->unsignedInteger('helmet')->default(0);
            $table->unsignedInteger('body')->default(0);
            $table->unsignedInteger('leftHand')->default(0);
            $table->unsignedInteger('rightHand')->default(0);
            $table->unsignedInteger('shoes')->default(0);
            $table->unsignedInteger('horse')->default(0);
            $table->unsignedInteger('bag')->default(0);
            $table->unsignedInteger('lastupdate')->default(0);
            $table->unsignedInteger('lastWaterBucketUse')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
