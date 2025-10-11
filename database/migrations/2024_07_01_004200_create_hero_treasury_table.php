<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_treasury', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hero_id')->constrained('hero_profiles');
            $table->string('artifact_code');
            $table->unsignedInteger('power')->default(0);
            $table->dateTime('acquired_at');
            $table->unique(['hero_id', 'artifact_code']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_treasury');
    }
};
