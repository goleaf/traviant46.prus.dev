<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds');
            $table->enum('kind', ['combat', 'scout', 'trade', 'system']);
            $table->unsignedBigInteger('for_user_id');
            $table->json('data');
            $table->timestamp('created_at')->useCurrent();

            $table->index('for_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
