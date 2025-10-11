<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alidata_diplomacy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alliance_id')->constrained('alliances');
            $table->foreignId('related_alliance_id')->constrained('alliances');
            $table->string('relationship_type');
            $table->date('established_on');
            $table->unique(['alliance_id', 'related_alliance_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alidata_diplomacy');
    }
};
