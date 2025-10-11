<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hero_id')->constrained('hero_profiles');
            $table->string('skill_name');
            $table->unsignedTinyInteger('points')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unique(['hero_id', 'skill_name']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_skills');
    }
};
