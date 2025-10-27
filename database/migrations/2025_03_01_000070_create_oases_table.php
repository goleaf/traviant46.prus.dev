<?php

declare(strict_types=1);

use App\Models\Game\World;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create the oases table to store world tiles that can host nature garrisons.
     */
    public function up(): void
    {
        Schema::create('oases', function (Blueprint $table) {
            /**
             * Primary metadata describing the oasis location and world relationship.
             */
            $table->id();
            $table->foreignIdFor(World::class, 'world_id')->constrained('worlds')->cascadeOnDelete();
            $table->integer('x');
            $table->integer('y');
            $table->unsignedTinyInteger('type');

            /**
             * State payload storing respawn schedule and defending nature troops.
             */
            $table->json('nature_garrison')->nullable();
            $table->timestamp('respawn_at')->nullable();
            $table->timestamps();

            $table->unique(['world_id', 'x', 'y']);
            $table->index('world_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oases');
    }
};
