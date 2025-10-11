<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('origin_village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('target_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->enum('delivery_type', ['manual', 'trade_route', 'aid', 'system'])->default('manual')->index();
            $table->enum('status', ['pending', 'en_route', 'arrived', 'cancelled'])->default('pending')->index();
            $table->json('resources');
            $table->unsignedTinyInteger('merchant_count')->default(0);
            $table->boolean('includes_hero')->default(false);
            $table->timestamp('dispatched_at')->nullable()->index();
            $table->timestamp('arrives_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['origin_village_id', 'dispatched_at'], 'resource_shipments_origin_dispatch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_shipments');
    }
};
