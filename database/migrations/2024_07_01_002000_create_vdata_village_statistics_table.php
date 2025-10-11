<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vdata_village_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('vdata_villages');
            $table->string('metric');
            $table->bigInteger('value')->default(0);
            $table->date('recorded_on');
            $table->unique(['village_id', 'metric', 'recorded_on']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vdata_village_statistics');
    }
};
