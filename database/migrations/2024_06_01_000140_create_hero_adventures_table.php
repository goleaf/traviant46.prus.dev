<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adventure', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid');
            $table->unsignedInteger('kid');
            $table->unsignedTinyInteger('dif');
            $table->unsignedInteger('time');
            $table->unsignedTinyInteger('end')->default(0);

            $table->index('uid');
            $table->index('kid');
            $table->index('time');
            $table->index('end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adventure');
    }
};
