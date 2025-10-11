<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fdata_field_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained('fdata_fields');
            $table->unsignedTinyInteger('level');
            $table->unsignedInteger('production')->default(0);
            $table->json('costs')->nullable();
            $table->unique(['field_id', 'level']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fdata_field_levels');
    }
};
