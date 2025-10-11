<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_account_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreignId('hero_id')->nullable()->constrained('heroes')->nullOnDelete();
            $table->string('reason', 120);
            $table->bigInteger('gold_delta')->default(0);
            $table->bigInteger('silver_delta')->default(0);
            $table->json('details')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
            $table->index(['user_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_account_entries');
    }
};
