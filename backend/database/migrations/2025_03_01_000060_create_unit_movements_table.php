<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('target_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->string('mission');
            $table->string('status')->default('travelling');
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('arrives_at');
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'arrives_at']);
            $table->index(['mission', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_movements');
    }
};

