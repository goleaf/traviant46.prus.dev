<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('recipient_alliance_id')->nullable();
            $table->string('visibility_scope', 32)->default('personal');
            $table->string('status', 24)->default('unread');
            $table->boolean('is_flagged')->default(false);
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('forwarded_at')->nullable();
            $table->string('share_token', 32)->nullable();
            $table->json('metadata')->nullable()->comment('Filters applied, loss thresholds, or farm list references.');
            $table->timestamps();
            $table->unique(['report_id', 'recipient_id']);
            $table->index(['recipient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_recipients');
    }
};
