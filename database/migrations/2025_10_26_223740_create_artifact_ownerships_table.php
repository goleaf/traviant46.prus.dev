<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifact_ownerships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('artifact_id')->constrained('artifacts')->cascadeOnDelete();
            $table->enum('scope', ['village', 'account']);
            $table->foreignId('village_id')->nullable()->constrained('villages')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamp('acquired_at');
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
            $table->unique('artifact_id');
            $table->index(['scope', 'village_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifact_ownerships');
    }
};
