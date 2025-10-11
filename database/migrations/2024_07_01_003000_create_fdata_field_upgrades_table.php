<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fdata_field_upgrades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained('fdata_fields');
            $table->unsignedTinyInteger('target_level');
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('queued_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fdata_field_upgrades');
    }
};
