<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('autoExtend');

        Schema::create('auto_extend_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key', 32);
            $table->unsignedTinyInteger('feature_type')->nullable()->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('last_checked_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_finished')->default(false);
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_extend_subscriptions');
    }
};
