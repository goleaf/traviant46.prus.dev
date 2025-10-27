<?php

declare(strict_types=1);

use App\Models\Game\World;
use App\Models\User;
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
        // Guard against recreating the table when earlier prototypes still exist in older dumps.
        if (Schema::hasTable('villages')) {
            return;
        }

        Schema::create('villages', function (Blueprint $table): void {
            /**
             * Primary identifier plus links back to the world and owner account.
             */
            $table->id();
            $table->foreignIdFor(World::class, 'world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'user_id')->nullable()->constrained('users')->nullOnDelete();

            /**
             * Cartesian map coordinates for the village location.
             */
            $table->integer('x');
            $table->integer('y');

            /**
             * Core status attributes mirrored from the Travian specification.
             */
            $table->boolean('is_capital')->default(false);
            $table->unsignedInteger('population')->default(0);
            $table->unsignedTinyInteger('loyalty')->default(100);
            $table->unsignedInteger('culture_points')->default(0);

            // Standard Laravel timestamps allow us to audit creation and update cycles.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('villages');
    }
};
