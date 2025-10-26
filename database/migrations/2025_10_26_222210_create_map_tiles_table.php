<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_tiles', function (Blueprint $table) {
            $table->id();
            $table->string('world_id');
            $table->integer('x');
            $table->integer('y');
            $table->unsignedTinyInteger('tile_type');
            $table->string('resource_pattern', 32);
            $table->unsignedTinyInteger('oasis_type')->nullable();
            $table->timestamps();

            $table->unique(['world_id', 'x', 'y']);
            $table->index('world_id');
            $table->index('tile_type');
            $table->index('oasis_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_tiles');
    }
};
