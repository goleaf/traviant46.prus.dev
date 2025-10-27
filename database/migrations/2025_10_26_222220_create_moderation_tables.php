<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create moderation tables that track bans, mutes, and multihunter interventions.
     */
    public function up(): void
    {
        Schema::create('bans', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scope', 32)->default('account');
            $table->string('reason', 255);
            $table->text('notes')->nullable();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('lifted_at')->nullable();
            $table->foreignIdFor(User::class, 'lifted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lifted_reason', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'issued_at']);
            $table->index(['scope', 'expires_at']);
            $table->index(['user_id', 'expires_at']);
        });

        Schema::create('mutes', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'muted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scope', 32)->default('messaging');
            $table->string('reason', 255);
            $table->text('notes')->nullable();
            $table->timestamp('muted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'scope']);
            $table->index(['user_id', 'expires_at']);
        });

        Schema::create('multihunter_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, 'multihunter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('category', 50)->default('moderation');
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('performed_at')->useCurrent();
            $table->ipAddress('ip_address')->nullable();
            $table->string('ip_address_hash', 128)->nullable();
            $table->json('metadata')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['multihunter_id', 'performed_at']);
            $table->index(['target_user_id', 'performed_at']);
            $table->index('category');
        });
    }

    /**
     * Roll back the moderation tables when the migration is reversed.
     */
    public function down(): void
    {
        Schema::dropIfExists('multihunter_actions');
        Schema::dropIfExists('mutes');
        Schema::dropIfExists('bans');
    }
};
