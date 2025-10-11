<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_summary_trends', function (Blueprint $table) {
            $table->id();
            $table->string('metric');
            $table->date('recorded_on');
            $table->decimal('average', 12, 2);
            $table->decimal('change', 8, 2)->default(0);
            $table->unique(['metric', 'recorded_on']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_summary_trends');
    }
};
