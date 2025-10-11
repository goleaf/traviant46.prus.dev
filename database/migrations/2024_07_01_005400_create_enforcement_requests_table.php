<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enforcement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_village_id')->constrained('vdata_villages');
            $table->foreignId('target_village_id')->constrained('vdata_villages');
            $table->json('requested_units')->nullable();
            $table->dateTime('needed_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enforcement_requests');
    }
};
