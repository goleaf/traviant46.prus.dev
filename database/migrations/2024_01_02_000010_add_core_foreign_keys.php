<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vdata', function (Blueprint $table) {
            $table->foreign('owner', 'vdata_owner_fk')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('fdata', function (Blueprint $table) {
            $table->foreign('kid', 'fdata_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
        });

        Schema::table('units', function (Blueprint $table) {
            $table->foreign('kid', 'units_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
        });

        Schema::table('farmlist', function (Blueprint $table) {
            $table->foreign('kid', 'farmlist_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
            $table->foreign('owner', 'farmlist_owner_fk')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('farmlist_last_reports', function (Blueprint $table) {
            $table->foreign('uid', 'farmlist_last_reports_uid_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('kid', 'farmlist_last_reports_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
        });

        Schema::table('market', function (Blueprint $table) {
            $table->foreign('kid', 'market_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
        });

        Schema::table('traderoutes', function (Blueprint $table) {
            $table->foreign('kid', 'traderoutes_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
            $table->foreign('to_kid', 'traderoutes_to_kid_fk')->references('kid')->on('vdata')->cascadeOnDelete();
        });

        Schema::table('autoExtend', function (Blueprint $table) {
            $table->foreign('uid', 'autoextend_uid_fk')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('buyGoldMessages', function (Blueprint $table) {
            $table->foreign('uid', 'buygoldmessages_uid_fk')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('mdata', function (Blueprint $table) {
            $table->foreign('uid', 'mdata_uid_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('to_uid', 'mdata_to_uid_fk')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('friendlist', function (Blueprint $table) {
            $table->foreign('uid', 'friendlist_uid_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('to_uid', 'friendlist_to_uid_fk')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->foreign('uid', 'notes_uid_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('to_uid', 'notes_to_uid_fk')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('messages_report', function (Blueprint $table) {
            $table->foreign('uid', 'messages_report_uid_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reported_uid', 'messages_report_reported_uid_fk')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages_report', function (Blueprint $table) {
            $table->dropForeign('messages_report_reported_uid_fk');
            $table->dropForeign('messages_report_uid_fk');
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropForeign('notes_to_uid_fk');
            $table->dropForeign('notes_uid_fk');
        });

        Schema::table('friendlist', function (Blueprint $table) {
            $table->dropForeign('friendlist_to_uid_fk');
            $table->dropForeign('friendlist_uid_fk');
        });

        Schema::table('mdata', function (Blueprint $table) {
            $table->dropForeign('mdata_to_uid_fk');
            $table->dropForeign('mdata_uid_fk');
        });

        Schema::table('buyGoldMessages', function (Blueprint $table) {
            $table->dropForeign('buygoldmessages_uid_fk');
        });

        Schema::table('autoExtend', function (Blueprint $table) {
            $table->dropForeign('autoextend_uid_fk');
        });

        Schema::table('traderoutes', function (Blueprint $table) {
            $table->dropForeign('traderoutes_to_kid_fk');
            $table->dropForeign('traderoutes_kid_fk');
        });

        Schema::table('market', function (Blueprint $table) {
            $table->dropForeign('market_kid_fk');
        });

        Schema::table('farmlist_last_reports', function (Blueprint $table) {
            $table->dropForeign('farmlist_last_reports_kid_fk');
            $table->dropForeign('farmlist_last_reports_uid_fk');
        });

        Schema::table('farmlist', function (Blueprint $table) {
            $table->dropForeign('farmlist_owner_fk');
            $table->dropForeign('farmlist_kid_fk');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropForeign('units_kid_fk');
        });

        Schema::table('fdata', function (Blueprint $table) {
            $table->dropForeign('fdata_kid_fk');
        });

        Schema::table('vdata', function (Blueprint $table) {
            $table->dropForeign('vdata_owner_fk');
        });
    }
};
