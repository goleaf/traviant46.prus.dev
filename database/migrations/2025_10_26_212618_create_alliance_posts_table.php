<?php

declare(strict_types=1);

use App\Models\Alliance;
use App\Models\AllianceTopic;
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
        Schema::create('alliance_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AllianceTopic::class, 'topic_id')->constrained('alliance_topics')->cascadeOnDelete();
            $table->foreignIdFor(Alliance::class, 'alliance_id')->constrained('alliances')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'acting_sitter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->foreignIdFor(User::class, 'edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            $table->index(['topic_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alliance_posts');
    }
};
