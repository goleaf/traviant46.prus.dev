<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifact_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('artifact_id')->constrained('artifacts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['artifact_id', 'user_id']);
            $table->index(['user_id', 'assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifact_user');
    }
};
