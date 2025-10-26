<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attack_dispatches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('target_village_id')->constrained('villages')->cascadeOnDelete();
            $table->timestamp('arrives_at')->index();
            $table->string('arrival_checksum', 6);
            $table->unsignedBigInteger('unit_slot_one_count')->default(0);
            $table->unsignedBigInteger('unit_slot_two_count')->default(0);
            $table->unsignedBigInteger('unit_slot_three_count')->default(0);
            $table->unsignedBigInteger('unit_slot_four_count')->default(0);
            $table->unsignedBigInteger('unit_slot_five_count')->default(0);
            $table->unsignedBigInteger('unit_slot_six_count')->default(0);
            $table->unsignedBigInteger('unit_slot_seven_count')->default(0);
            $table->unsignedBigInteger('unit_slot_eight_count')->default(0);
            $table->unsignedBigInteger('unit_slot_nine_count')->default(0);
            $table->unsignedBigInteger('unit_slot_ten_count')->default(0);
            $table->boolean('includes_hero')->default(false);
            $table->unsignedTinyInteger('attack_type');
            $table->boolean('redeploy_hero')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attack_dispatches');
    }
};
