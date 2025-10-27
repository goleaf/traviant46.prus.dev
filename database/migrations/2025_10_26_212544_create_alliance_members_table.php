<?php

declare(strict_types=1);

use App\Models\Alliance;
use App\Models\AllianceRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create alliance role and membership tables that back roster management.
     */
    public function up(): void
    {
        Schema::create('alliance_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Alliance::class, 'alliance_id')->constrained('alliances')->cascadeOnDelete();
            $table->string('name');
            $table->json('permissions')->nullable();
            $table->timestamps();

            $table->unique(['alliance_id', 'name']);
        });

        Schema::create('alliance_members', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Alliance::class, 'alliance_id')->constrained('alliances')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(AllianceRole::class, 'alliance_role_id')->nullable()->constrained('alliance_roles')->nullOnDelete();
            $table->string('role', 50)->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['alliance_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alliance_members');
        Schema::dropIfExists('alliance_roles');
    }
};
