<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->json('give');
            $table->json('want');
            $table->unsignedTinyInteger('merchants');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_offers');
    }
};
