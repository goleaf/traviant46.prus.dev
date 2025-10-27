<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the trades table storing active merchant shipments.
     */
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('origin')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('target')->constrained('villages')->cascadeOnDelete();
            $table->json('payload');
            $table->timestamp('eta');
            $table->timestamps();

            $table->index('eta');
        });
    }

    /**
     * Drop the trades table.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
