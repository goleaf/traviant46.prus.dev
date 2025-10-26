<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('login_ip_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('reputation_score')->nullable()->after('ip_address_numeric');
            $table->json('reputation_details')->nullable()->after('reputation_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('login_ip_logs', function (Blueprint $table) {
            $table->dropColumn(['reputation_score', 'reputation_details']);
        });
    }
};
