<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('world_casualty_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attack_count')->default(0);
            $table->unsignedBigInteger('casualty_count')->default(0);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('world_casualty_snapshots');
    }
};
