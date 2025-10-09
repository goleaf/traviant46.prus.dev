<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('origin_village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('target_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->json('resources')->nullable();
            $table->unsignedInteger('dispatch_interval_minutes')->default(60);
            $table->timestamp('next_dispatch_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['user_id', 'is_active']);
            $table->index('next_dispatch_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_routes');
    }
};
