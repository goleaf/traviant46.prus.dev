<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_quest', function (Blueprint $table) {
            $table->foreign('uid', 'daily_quest_uid_fk')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('adventure', function (Blueprint $table) {
            $table->foreign('uid', 'adventure_uid_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('kid', 'adventure_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
        });

        Schema::table('a2b', function (Blueprint $table) {
            $table->foreign('to_kid', 'a2b_to_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
        });

        Schema::table('movement', function (Blueprint $table) {
            $table->foreign('kid', 'movement_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
            $table->foreign('to_kid', 'movement_to_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
        });

        Schema::table('send', function (Blueprint $table) {
            $table->foreign('kid', 'send_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
            $table->foreign('to_kid', 'send_to_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
        });

        Schema::table('training', function (Blueprint $table) {
            $table->foreign('kid', 'training_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
        });

        Schema::table('raidlist', function (Blueprint $table) {
            $table->foreign('lid', 'raidlist_lid_fk')->references('id')->on('farmlist')->cascadeOnDelete();
            $table->foreign('kid', 'raidlist_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
        });

        Schema::table('enforcement', function (Blueprint $table) {
            $table->foreign('uid', 'enforcement_uid_fk')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enforcement', function (Blueprint $table) {
            $table->dropForeign('enforcement_uid_fk');
        });

        Schema::table('raidlist', function (Blueprint $table) {
            $table->dropForeign('raidlist_kid_fk');
            $table->dropForeign('raidlist_lid_fk');
        });

        Schema::table('training', function (Blueprint $table) {
            $table->dropForeign('training_kid_fk');
        });

        Schema::table('send', function (Blueprint $table) {
            $table->dropForeign('send_to_kid_fk');
            $table->dropForeign('send_kid_fk');
        });

        Schema::table('movement', function (Blueprint $table) {
            $table->dropForeign('movement_to_kid_fk');
            $table->dropForeign('movement_kid_fk');
        });

        Schema::table('a2b', function (Blueprint $table) {
            $table->dropForeign('a2b_to_kid_fk');
        });

        Schema::table('adventure', function (Blueprint $table) {
            $table->dropForeign('adventure_kid_fk');
            $table->dropForeign('adventure_uid_fk');
        });

        Schema::table('daily_quest', function (Blueprint $table) {
            $table->dropForeign('daily_quest_uid_fk');
        });
    }
};
