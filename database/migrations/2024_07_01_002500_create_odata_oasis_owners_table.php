<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odata_oasis_owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oasis_id')->constrained('odata_oases');
            $table->foreignId('owner_id')->nullable()->constrained('users');
            $table->foreignId('village_id')->nullable()->constrained('vdata_villages');
            $table->dateTime('claimed_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odata_oasis_owners');
    }
};
