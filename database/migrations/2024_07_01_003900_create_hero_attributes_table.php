<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hero_id')->constrained('hero_profiles');
            $table->string('attribute');
            $table->integer('value')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->unique(['hero_id', 'attribute']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_attributes');
    }
};
