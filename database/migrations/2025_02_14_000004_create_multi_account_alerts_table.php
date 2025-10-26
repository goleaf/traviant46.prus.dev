<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('multi_account_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->foreignIdFor(User::class, 'primary_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'conflict_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamps();
            $table->unique(['ip_address', 'primary_user_id', 'conflict_user_id'], 'multi_account_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multi_account_alerts');
    }
};
