<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odata_oasis_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oasis_id')->constrained('odata_oases');
            $table->string('event_type');
            $table->json('details')->nullable();
            $table->dateTime('recorded_at');
            $table->index(['oasis_id', 'recorded_at']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odata_oasis_history');
    }
};
