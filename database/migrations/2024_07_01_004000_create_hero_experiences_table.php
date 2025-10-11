<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hero_id')->constrained('hero_profiles');
            $table->unsignedInteger('gained_experience');
            $table->string('source');
            $table->dateTime('gained_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_experiences');
    }
};
