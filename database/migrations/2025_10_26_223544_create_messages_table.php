<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, 'from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'to_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject', 255);
            $table->text('body');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
