<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('alliance_id')->nullable()->constrained('alliances')->nullOnDelete();
            $table->string('name', 50);
            $table->integer('x_coordinate');
            $table->integer('y_coordinate');
            $table->boolean('is_capital')->default(false)->index();
            $table->unsignedInteger('population')->default(2);
            $table->unsignedInteger('culture_points')->default(0);
            $table->json('storage')->nullable();
            $table->json('production')->nullable();
            $table->json('defense_bonus')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['x_coordinate', 'y_coordinate']);
            $table->index(['user_id', 'is_capital']);
            $table->index(['alliance_id', 'population']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
    }
};
