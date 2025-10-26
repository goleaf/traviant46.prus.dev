<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('gid')->nullable()->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->unsignedTinyInteger('max_level')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_types');
    }
};
