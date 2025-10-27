<?php

declare(strict_types=1);

use App\Models\Game\World;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the map_tiles table for storing normalized world tile metadata.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('map_tiles')) {
            return;
        }

        Schema::create('map_tiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(World::class, 'world_id')->constrained('worlds')->cascadeOnDelete()->comment('Links each tile to its Travian world instance.');
            $table->integer('x')->comment('Horizontal coordinate on the world grid.');
            $table->integer('y')->comment('Vertical coordinate on the world grid.');
            $table->unsignedTinyInteger('tile_type')->comment('Enumerated terrain type derived from the legacy map.');
            $table->string('resource_pattern', 32)->comment('Resource distribution signature for the tile.');
            $table->unsignedTinyInteger('oasis_type')->nullable()->comment('Nullable oasis classification for oasis-bearing tiles.');

            $table->unique(['world_id', 'x', 'y'], 'map_tiles_world_coordinates_unique');
            $table->index('world_id');
            $table->index('tile_type');
            $table->index('oasis_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_tiles');
    }
};
