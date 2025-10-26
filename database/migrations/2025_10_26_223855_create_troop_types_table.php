<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('troop_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('tribe')->index();
            $table->string('code');
            $table->string('name');
            $table->unsignedSmallInteger('attack');
            $table->unsignedSmallInteger('def_inf');
            $table->unsignedSmallInteger('def_cav');
            $table->unsignedSmallInteger('speed');
            $table->unsignedSmallInteger('carry');
            $table->json('train_cost');
            $table->unsignedTinyInteger('upkeep');
            $table->timestamps();
            $table->unique(['tribe', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('troop_types');
    }
};
