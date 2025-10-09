<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_faces', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hero_id')->constrained('heroes')->cascadeOnDelete();
            $table->enum('gender', ['male', 'female', 'non_binary'])->default('male');
            $table->string('skin_tone', 20)->default('light');
            $table->string('hair_color', 20)->nullable();
            $table->string('hair_style', 40)->nullable();
            $table->string('eye_color', 20)->nullable();
            $table->string('facial_hair', 40)->nullable();
            $table->json('features')->nullable();
            $table->timestamps();

            $table->unique('hero_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_faces');
    }
};
