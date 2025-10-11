<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitter_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'account_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'sitter_id')->constrained('users')->cascadeOnDelete();
            $table->json('permissions')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['account_id', 'sitter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitter_assignments');
    }
};
