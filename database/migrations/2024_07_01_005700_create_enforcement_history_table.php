<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enforcement_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('enforcement_requests');
            $table->string('status');
            $table->json('details')->nullable();
            $table->dateTime('changed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enforcement_history');
    }
};
