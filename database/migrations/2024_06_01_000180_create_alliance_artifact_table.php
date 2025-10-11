<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alliance_artifact', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('alliance_id')->constrained('alliances')->cascadeOnDelete();
            $table->foreignId('artifact_id')->constrained('artifacts')->cascadeOnDelete();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->unique(['alliance_id', 'artifact_id']);
            $table->index(['artifact_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alliance_artifact');
    }
};
