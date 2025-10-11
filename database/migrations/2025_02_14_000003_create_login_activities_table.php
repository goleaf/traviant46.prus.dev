<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'acting_sitter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->ipAddress('ip_address');
            $table->string('user_agent', 1024)->nullable();
            $table->boolean('via_sitter')->default(false);
            $table->timestamps();
            $table->index(['ip_address', 'via_sitter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_activities');
    }
};
