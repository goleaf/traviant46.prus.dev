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
            $table->foreignId('owner_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->integer('population')->default(0);
            $table->unsignedTinyInteger('loyalty')->default(100);
            $table->integer('x_coordinate');
            $table->integer('y_coordinate');
            $table->boolean('is_capital')->default(false);
            $table->timestamp('founded_at')->nullable();
            $table->timestamps();
            $table->index(['x_coordinate', 'y_coordinate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
    }
};
