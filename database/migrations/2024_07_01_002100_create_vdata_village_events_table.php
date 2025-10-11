<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vdata_village_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('vdata_villages');
            $table->string('event_type');
            $table->json('payload')->nullable();
            $table->dateTime('occurred_at');
            $table->index(['village_id', 'occurred_at']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vdata_village_events');
    }
};
