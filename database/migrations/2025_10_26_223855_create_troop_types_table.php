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
        /**
         * Create the canonical troop type catalog storing immutable base statistics.
         */
        Schema::create('troop_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('tribe')->index()->comment('Travian tribe identifier including nature and natars.');
            $table->string('code')->comment('Stable machine readable identifier, e.g. romans-legionnaire.');
            $table->string('name')->comment('Localized unit name.');
            $table->unsignedSmallInteger('attack')->comment('Base attack value.');
            $table->unsignedSmallInteger('def_inf')->comment('Base defense against infantry.');
            $table->unsignedSmallInteger('def_cav')->comment('Base defense against cavalry.');
            $table->unsignedSmallInteger('speed')->comment('Tiles per hour movement speed.');
            $table->unsignedSmallInteger('carry')->comment('Maximum carry capacity for resources.');
            $table->json('train_cost')->comment('Resource costs required to train or spawn the unit.');
            $table->unsignedTinyInteger('upkeep')->comment('Crop upkeep per hour.');
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
