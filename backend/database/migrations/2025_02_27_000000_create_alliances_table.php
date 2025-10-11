<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alliances', function (Blueprint $table) {
            $table->id();
            $table->string('tag', 8)->unique();
            $table->string('name');
            $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->json('preferences')->nullable();
            $table->json('statistics')->nullable();
            $table->timestamp('founded_at')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('founded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alliances');
    }
};
