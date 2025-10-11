<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alidata_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alliance_id')->constrained('alliances');
            $table->string('role_name');
            $table->string('role_key');
            $table->boolean('is_default')->default(false);
            $table->unique(['alliance_id', 'role_key']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alidata_roles');
    }
};
