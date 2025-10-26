<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_quest_progresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('quest_one_progress')->default(0);
            $table->unsignedTinyInteger('quest_two_progress')->default(0);
            $table->unsignedTinyInteger('quest_three_progress')->default(0);
            $table->unsignedTinyInteger('quest_four_progress')->default(0);
            $table->unsignedTinyInteger('quest_five_progress')->default(0);
            $table->unsignedTinyInteger('quest_six_progress')->default(0);
            $table->unsignedTinyInteger('quest_seven_progress')->default(0);
            $table->unsignedTinyInteger('quest_eight_progress')->default(0);
            $table->unsignedTinyInteger('quest_nine_progress')->default(0);
            $table->unsignedTinyInteger('quest_ten_progress')->default(0);
            $table->unsignedTinyInteger('quest_eleven_progress')->default(0);
            $table->unsignedBigInteger('alliance_contribution_total')->default(0);
            $table->unsignedTinyInteger('reward_one_type')->default(0);
            $table->boolean('reward_one_claimed')->default(false);
            $table->unsignedTinyInteger('reward_two_type')->default(0);
            $table->boolean('reward_two_claimed')->default(false);
            $table->unsignedTinyInteger('reward_three_type')->default(0);
            $table->boolean('reward_three_claimed')->default(false);
            $table->unsignedTinyInteger('reward_four_type')->default(0);
            $table->boolean('reward_four_claimed')->default(false);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_quest_progresses');
    }
};
