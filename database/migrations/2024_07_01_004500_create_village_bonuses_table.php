<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('vdata_villages');
            $table->string('bonus_type');
            $table->unsignedInteger('value')->default(0);
            $table->date('applied_on');
            $table->unique(['village_id', 'bonus_type', 'applied_on']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_bonuses');
    }
};
