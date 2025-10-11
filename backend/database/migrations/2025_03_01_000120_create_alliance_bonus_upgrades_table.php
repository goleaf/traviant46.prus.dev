<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alliance_bonus_upgrades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('alliance_id')->constrained('alliances')->cascadeOnDelete();
            $table->string('bonus_type');
            $table->unsignedInteger('target_level');
            $table->timestamp('completes_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['bonus_type', 'completes_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alliance_bonus_upgrades');
    }
};
