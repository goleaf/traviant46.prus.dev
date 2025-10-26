<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitter_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'sitter_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('permissions')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['owner_user_id', 'sitter_user_id'], 'sitter_delegations_unique_owner_sitter');
            $table->index(['owner_user_id', 'expires_at'], 'sitter_delegations_owner_expires');
            $table->index(['sitter_user_id', 'expires_at'], 'sitter_delegations_sitter_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitter_delegations');
    }
};
