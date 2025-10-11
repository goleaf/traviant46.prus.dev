<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('forum_boards');
            $table->string('permission_key');
            $table->string('target_type');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_permissions');
    }
};
