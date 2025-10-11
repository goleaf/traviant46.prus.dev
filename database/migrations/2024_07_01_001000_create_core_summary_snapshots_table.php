<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_summary_snapshots', function (Blueprint $table) {
            $table->id();
            $table->dateTime('captured_at');
            $table->string('scope');
            $table->json('payload');
            $table->unique(['captured_at', 'scope']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_summary_snapshots');
    }
};
