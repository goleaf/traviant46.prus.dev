<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifact_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('artifact_id')->constrained('artifacts')->cascadeOnDelete();
            $table->foreignId('captor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('captor_village_id')->constrained('villages')->cascadeOnDelete();
            $table->string('captor_name_snapshot', 40);
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index(['artifact_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifact_logs');
    }
};
