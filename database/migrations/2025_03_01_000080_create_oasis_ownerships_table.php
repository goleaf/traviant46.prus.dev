<?php

declare(strict_types=1);

use App\Models\Game\Village;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create the pivot table linking villages to the oases they control.
     */
    public function up(): void
    {
        Schema::create('oasis_ownerships', function (Blueprint $table) {
            /**
             * Foreign keys ensure the relationship stays synchronized with core village and oasis records.
             */
            $table->foreignIdFor(Village::class, 'village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('oasis_id')->constrained('oases')->cascadeOnDelete();
            $table->primary(['village_id', 'oasis_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oasis_ownerships');
    }
};
