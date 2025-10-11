<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alidata_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alliance_id')->constrained('alliances');
            $table->string('motto')->nullable();
            $table->text('description')->nullable();
            $table->string('banner_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alidata_profiles');
    }
};
