<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid');
            $table->string('cause', 100);
            $table->integer('reserve');
            $table->unsignedInteger('balance');
            $table->unsignedInteger('time');

            $table->index(['uid', 'balance', 'time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting');
    }
};
