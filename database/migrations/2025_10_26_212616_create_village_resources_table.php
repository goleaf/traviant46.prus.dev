<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_resources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->string('resource_type', 16);
            $table->unsignedTinyInteger('level')->default(0);
            $table->unsignedInteger('production_per_hour')->default(0);
            $table->unsignedInteger('storage_capacity')->default(0);
            $table->json('bonuses')->nullable()->comment('Modifier payload covering oasis, artifacts, and alliance bonuses.');
            $table->timestamp('last_collected_at')->nullable();
            $table->timestamps();
            $table->unique(['village_id', 'resource_type']);
            $table->index(['resource_type', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_resources');
    }
};
