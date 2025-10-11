<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifact_effects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artifact_id')->constrained('artifacts');
            $table->string('effect_key');
            $table->json('modifiers')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unique(['artifact_id', 'effect_key']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifact_effects');
    }
};
