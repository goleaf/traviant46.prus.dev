<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration responsible for tracking which village or account currently wields each artifact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifact_ownerships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('artifact_id')->constrained('artifacts')->cascadeOnDelete()->comment('Artifact definition being wielded.');
            $table->enum('scope', ['village', 'account'])->comment('Mirror of the artifact scope so ownership context stays explicit.');
            $table->foreignId('village_id')->nullable()->constrained('villages')->cascadeOnDelete()->comment('Village owning the artifact when scoped to a single settlement.');
            $table->foreignId('account_id')->nullable()->constrained('users')->cascadeOnDelete()->comment('Account owning the artifact when scoped globally.');
            $table->timestamp('acquired_at')->comment('Timestamp when the artifact was captured.');
            $table->timestamp('activated_at')->nullable()->comment('Timestamp when the artifact effect became active.');
            $table->timestamps();
            $table->unique('artifact_id');
            $table->index(['scope', 'village_id', 'account_id']);
            $table->check("(scope = 'village' AND village_id IS NOT NULL AND account_id IS NULL) OR (scope = 'account' AND account_id IS NOT NULL AND village_id IS NULL)");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifact_ownerships');
    }
};
