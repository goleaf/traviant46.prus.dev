<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_summary_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('core_summary_snapshots');
            $table->string('metric');
            $table->bigInteger('value')->default(0);
            $table->unique(['snapshot_id', 'metric']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_summary_metrics');
    }
};
