<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('category', 50);
            $table->string('type', 50)->nullable();
            $table->string('subject');
            $table->text('summary')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['recipient_id', 'category']);
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
