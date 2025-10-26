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
     */
    public function up(): void
    {
        Schema::create('oases', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(World::class, 'world_id')->constrained('worlds')->cascadeOnDelete();
            $table->integer('x');
            $table->integer('y');
            $table->unsignedTinyInteger('type');
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
