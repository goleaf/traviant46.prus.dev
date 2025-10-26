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
     */
    public function up(): void
    {
        Schema::create('oasis_ownerships', function (Blueprint $table) {
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
