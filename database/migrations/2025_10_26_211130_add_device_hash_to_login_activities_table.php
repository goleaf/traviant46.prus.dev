<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_activities', function (Blueprint $table) {
            $table->string('device_hash', 64)->nullable()->after('user_agent');
            $table->json('fingerprint_snapshot')->nullable()->after('device_hash');
            $table->index('device_hash');
        });
    }

    public function down(): void
    {
        Schema::table('login_activities', function (Blueprint $table) {
            $table->dropIndex(['device_hash']);
            $table->dropColumn(['device_hash', 'fingerprint_snapshot']);
        });
    }
};
