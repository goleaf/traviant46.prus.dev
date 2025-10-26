<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('build_queues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->unsignedSmallInteger('building_type');
            $table->unsignedTinyInteger('target_level');
            $table->timestamp('finishes_at');
            $table->enum('state', ['pending', 'working', 'done'])->default('pending');
            $table->timestamps();
            $table->index('finishes_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('build_queues');
    }
};
