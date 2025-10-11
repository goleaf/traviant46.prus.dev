<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wonders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('level')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['level', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wonders');
    }
};
