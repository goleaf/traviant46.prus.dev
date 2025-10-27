<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the messages table for direct communications between users.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            // Auto-incrementing identifier for the message record.
            $table->id();

            // Link the sender of the message to the users table.
            $table->foreignIdFor(User::class, 'from_user_id')->constrained('users')->cascadeOnDelete();

            // Link the recipient of the message to the users table.
            $table->foreignIdFor(User::class, 'to_user_id')->constrained('users')->cascadeOnDelete();

            // Store the subject and body content of the message.
            $table->string('subject', 255);
            $table->text('body');

            // Track when a recipient reads the message.
            $table->timestamp('read_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
