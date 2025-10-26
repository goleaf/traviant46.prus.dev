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
        Schema::create('troops', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Village::class)->constrained('villages')->cascadeOnDelete();
            $table->unsignedSmallInteger('troop_type_id');
            $table->unsignedBigInteger('amount')->default(0);
            $table->timestamps();

            $table->unique(['village_id', 'troop_type_id']);
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
