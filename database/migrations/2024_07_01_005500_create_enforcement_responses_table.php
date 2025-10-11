<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enforcement_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('enforcement_requests');
            $table->foreignId('responder_village_id')->constrained('vdata_villages');
            $table->json('committed_units')->nullable();
            $table->dateTime('departing_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enforcement_responses');
    }
};
