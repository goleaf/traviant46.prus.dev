<?php

declare(strict_types=1);

use App\Models\Game\TroopType;
use App\Models\Game\Village;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration responsible for storing aggregated troop counts per village and troop type.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('troops', function (Blueprint $table): void {
            $table->id()->comment('Primary key for the troop stack record.');
            $table->foreignIdFor(Village::class)
                ->constrained('villages')
                ->cascadeOnDelete()
                ->comment('Owning village for the aggregated troop stack.');
            $table->foreignIdFor(TroopType::class, 'troop_type_id')
                ->constrained('troop_types')
                ->cascadeOnDelete()
                ->comment('Troop type associated with the stored count.');
            $table->unsignedBigInteger('amount')
                ->default(0)
                ->comment('Total number of troops of the given type stationed in the village.');
            $table->timestamps();

            $table->unique(['village_id', 'troop_type_id']);
            $table->comment('Aggregated troop counts per village and troop type.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('troops');
    }
};
