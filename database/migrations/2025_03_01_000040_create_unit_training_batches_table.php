<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_training_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->unsignedSmallInteger('unit_type_id');
            $table->unsignedInteger('quantity');
            $table->unsignedTinyInteger('queue_position')->default(0);
            $table->string('training_building')->nullable();
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('completes_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'completes_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_training_batches');
    }
};
