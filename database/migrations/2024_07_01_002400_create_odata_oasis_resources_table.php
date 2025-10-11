<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odata_oasis_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oasis_id')->constrained('odata_oases');
            $table->unsignedInteger('wood');
            $table->unsignedInteger('clay');
            $table->unsignedInteger('iron');
            $table->unsignedInteger('crop');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odata_oasis_resources');
    }
};
