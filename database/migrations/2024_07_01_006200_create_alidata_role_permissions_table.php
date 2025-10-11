<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alidata_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('alidata_roles');
            $table->string('permission_key');
            $table->boolean('is_allowed')->default(true);
            $table->unique(['role_id', 'permission_key']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alidata_role_permissions');
    }
};
