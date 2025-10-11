<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odata_oasis_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oasis_id')->constrained('odata_oases');
            $table->string('reward_type');
            $table->unsignedInteger('value')->default(0);
            $table->string('frequency')->default('daily');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odata_oasis_rewards');
    }
};
