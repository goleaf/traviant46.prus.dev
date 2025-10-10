<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('gid')->unique();
            $table->string('name');
            $table->unsignedTinyInteger('type')->nullable()->index();
            $table->unsignedTinyInteger('max_level')->nullable();
            $table->unsignedTinyInteger('extra')->nullable();
            $table->unsignedTinyInteger('crop_consumption')->nullable();
            $table->unsignedTinyInteger('culture_points')->nullable();
            $table->decimal('growth_factor', 12, 4)->nullable();
            $table->json('cost')->nullable();
            $table->json('time')->nullable();
            $table->json('requirements')->nullable();
            $table->json('building_requirements')->nullable();
            $table->boolean('capital_only')->default(false);
            $table->json('attributes')->nullable();
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_types');
    }
};
