<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vdata_village_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('vdata_villages');
            $table->dateTime('captured_at');
            $table->unsignedInteger('population')->default(0);
            $table->json('resources')->nullable();
            $table->unique(['village_id', 'captured_at']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vdata_village_snapshots');
    }
};
