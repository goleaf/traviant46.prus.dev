<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wdata_worlds', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('speed')->default(1);
            $table->date('started_on');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wdata_worlds');
    }
};
