<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('race')->index();
            $table->unsignedTinyInteger('slot');
            $table->string('unit_type', 2)->nullable();
            $table->unsignedSmallInteger('attack')->default(0);
            $table->unsignedSmallInteger('defense_infantry')->default(0);
            $table->unsignedSmallInteger('defense_cavalry')->default(0);
            $table->unsignedTinyInteger('speed')->default(0);
            $table->unsignedSmallInteger('capacity')->default(0);
            $table->unsignedTinyInteger('crop_consumption')->default(0);
            $table->unsignedInteger('training_time')->default(0);
            $table->unsignedInteger('research_time')->nullable();
            $table->unsignedInteger('mask')->nullable();
            $table->json('cost')->nullable();
            $table->json('building_requirements')->nullable();
            $table->json('attributes')->nullable();
            $table->timestamps();
            $table->unique(['race', 'slot']);
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_stats');
    }
};
