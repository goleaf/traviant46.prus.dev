<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifacts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('artifact_type');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('effect_payload')->nullable();
            $table->unsignedInteger('effect_interval_minutes')->default(60);
            $table->timestamp('last_effect_applied_at')->nullable();
            $table->timestamp('next_effect_at')->nullable();
            $table->timestamps();
            $table->index(['artifact_type', 'next_effect_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifacts');
    }
};
