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
            $table->unsignedBigInteger('legacy_report_id')->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('alliance_id')->nullable();
            $table->foreignId('origin_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->foreignId('target_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->string('report_type', 32);
            $table->string('category', 32)->nullable();
            $table->string('delivery_scope', 32)->default('personal');
            $table->boolean('is_system_generated')->default(false);
            $table->boolean('is_persistent')->default(false);
            $table->unsignedTinyInteger('loss_percentage')->nullable();
            $table->json('payload')->nullable()->comment('Structured report data previously serialized via NoticeHelper.');
            $table->json('bounty')->nullable()->comment('Resource haul snapshot with wood/clay/iron/crop keys.');
            $table->timestamp('triggered_at')->nullable()->index();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->json('metadata')->nullable()->comment('Additional battle metadata, share tokens, farm list references.');
            $table->timestamps();
            $table->index(['user_id', 'report_type']);
            $table->index(['target_village_id', 'report_type'], 'reports_target_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
