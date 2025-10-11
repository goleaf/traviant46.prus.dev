<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face', function (Blueprint $table): void {
            $table->unsignedInteger('uid')->primary();
            $table->unsignedSmallInteger('headProfile');
            $table->unsignedSmallInteger('hairColor');
            $table->unsignedSmallInteger('hairStyle');
            $table->unsignedSmallInteger('ears');
            $table->unsignedSmallInteger('eyebrow');
            $table->unsignedSmallInteger('eyes');
            $table->unsignedSmallInteger('nose');
            $table->unsignedSmallInteger('mouth');
            $table->unsignedSmallInteger('beard');
            $table->string('gender', 6)->default('male');
            $table->unsignedInteger('lastupdate')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face');
    }
};
