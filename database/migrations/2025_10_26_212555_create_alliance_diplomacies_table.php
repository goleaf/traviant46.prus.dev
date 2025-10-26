<?php

declare(strict_types=1);

use App\Models\Alliance;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alliance_diplomacies', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Alliance::class, 'alliance_id')->constrained('alliances')->cascadeOnDelete();
            $table->foreignIdFor(Alliance::class, 'target_alliance_id')->constrained('alliances')->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->foreignIdFor(User::class, 'initiated_by')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['alliance_id', 'target_alliance_id', 'type', 'status'], 'alliance_diplomacy_unique');
            $table->index(['target_alliance_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alliance_diplomacies');
    }
};
