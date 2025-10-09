<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('casualties');

        Schema::create('combat_casualties', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attacks_count')->default(0);
            $table->unsignedBigInteger('casualties_count')->default(0);
            $table->timestamp('recorded_for')->nullable()->index();
            $table->timestamps();

            $table->index(['attacks_count', 'casualties_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combat_casualties');
    }
};
