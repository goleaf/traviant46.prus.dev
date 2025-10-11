<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid');
            $table->unsignedTinyInteger('btype');
            $table->unsignedSmallInteger('type');
            $table->unsignedBigInteger('num');
            $table->unsignedInteger('placeId');
            $table->unsignedTinyInteger('proc')->default(0);

            $table->index('uid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
