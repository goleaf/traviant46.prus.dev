<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifact_ownerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artifact_id')->constrained('artifacts');
            $table->foreignId('owner_id')->nullable()->constrained('users');
            $table->foreignId('village_id')->nullable()->constrained('vdata_villages');
            $table->dateTime('acquired_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifact_ownerships');
    }
};
