<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Handle moderation entities such as bans, mutes, and multihunter audit trails.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bans', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scope', 32)->default('account')->comment('Defines the system surface affected by the ban.');
            $table->string('reason', 255);
            $table->text('notes')->nullable();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('lifted_at')->nullable();
            $table->foreignIdFor(User::class, 'lifted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lifted_reason', 255)->nullable();
            $table->json('metadata')->nullable()->comment('Stores contextual fields mirrored from the moderation dashboard.');
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
            $table->string('scope', 32)->default('messaging')->comment('Describes the communication domain restricted by the mute.');
            $table->string('reason', 255);
            $table->text('notes')->nullable();
            $table->timestamp('muted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable()->comment('Captures auxiliary fields such as report IDs or automation payloads.');
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
            $table->string('category', 50)->default('moderation')->comment('Groupings such as moderation, investigation, or automation.');
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('performed_at')->useCurrent();
            $table->ipAddress('ip_address')->nullable();
            $table->string('ip_address_hash', 128)->nullable();
            $table->json('metadata')->nullable()->comment('Payload mirror for external systems (e.g., ticket IDs).');
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['multihunter_id', 'performed_at']);
            $table->index(['target_user_id', 'performed_at']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multihunter_actions');
        Schema::dropIfExists('mutes');
        Schema::dropIfExists('bans');
    }
};
