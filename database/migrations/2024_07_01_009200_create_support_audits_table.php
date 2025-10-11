<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_audits', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->foreignId('performed_by')->nullable()->constrained('users');
            $table->json('details')->nullable();
            $table->dateTime('performed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_audits');
    }
};
