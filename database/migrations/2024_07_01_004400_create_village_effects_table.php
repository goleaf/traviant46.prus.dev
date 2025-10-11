<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_effects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('vdata_villages');
            $table->string('effect_type');
            $table->json('modifiers')->nullable();
            $table->dateTime('activated_at');
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_effects');
    }
};
