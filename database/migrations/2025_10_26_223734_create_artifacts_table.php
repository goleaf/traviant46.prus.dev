<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifacts', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('scope', ['village', 'account']);
            $table->json('effect');
            $table->unsignedTinyInteger('treasury_level_req');
            $table->timestamps();
            $table->index('scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifacts');
    }
};
