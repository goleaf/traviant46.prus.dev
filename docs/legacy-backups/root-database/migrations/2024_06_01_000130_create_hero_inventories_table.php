<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_inventories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hero_id')->constrained('heroes')->cascadeOnDelete();
            $table->unsignedSmallInteger('capacity')->default(18);
            $table->unsignedTinyInteger('extra_slots')->default(0);
            $table->timestamp('last_water_bucket_used_at')->nullable();
            $table->json('state')->nullable();
            $table->timestamps();

            $table->unique('hero_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_inventories');
    }
};
