<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fdata_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('vdata_villages');
            $table->unsignedTinyInteger('slot');
            $table->string('field_type');
            $table->unsignedTinyInteger('level')->default(0);
            $table->unique(['village_id', 'slot']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fdata_fields');
    }
};
