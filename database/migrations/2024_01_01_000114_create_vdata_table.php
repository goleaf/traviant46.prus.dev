<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `vdata`
(
  `kid`                 INT(6) UNSIGNED         NOT NULL,
  `owner`               INT(11) UNSIGNED        NOT NULL,
  `fieldtype`           TINYINT(2) UNSIGNED     NOT NULL,
  `name`                VARCHAR(45)             NOT NULL,
  `capital`             TINYINT(1) UNSIGNED     NOT NULL,
  `pop`                 INT(10)                 NOT NULL,
  `cp`                  INT(10)                 NOT NULL,
  `celebration`         INT(11)                 NOT NULL DEFAULT '0',
  `festival`            INT(11)                 NOT NULL DEFAULT '0',
  `type`                TINYINT(2)              NOT NULL DEFAULT '0',
  `wood`                DOUBLE(50, 4)           NOT NULL DEFAULT '0',
  `clay`                DOUBLE(50, 4)           NOT NULL DEFAULT '0',
  `iron`                DOUBLE(50, 4)           NOT NULL DEFAULT '0',
  `woodp`               BIGINT(50)              NOT NULL DEFAULT '0',
  `clayp`               BIGINT(50)              NOT NULL DEFAULT '0',
  `ironp`               BIGINT(50)              NOT NULL DEFAULT '0',
  `maxstore`            BIGINT(50)              NOT NULL,
  `extraMaxstore`       INT(5) UNSIGNED         NOT NULL DEFAULT '0',
  `crop`                DOUBLE(50, 4)           NOT NULL DEFAULT '0',
  `cropp`               BIGINT(50)              NOT NULL DEFAULT '0',
  `maxcrop`             BIGINT(50)              NOT NULL,
  `extraMaxcrop`        INT(5) UNSIGNED         NOT NULL DEFAULT '0',
  `upkeep`              BIGINT(50)              NOT NULL DEFAULT '0',
  `last_loyalty_update` INT(10) UNSIGNED        NOT NULL DEFAULT '0',
  `lastmupdate`         BIGINT(15) UNSIGNED     NOT NULL DEFAULT '0',
  `loyalty`             DOUBLE(13, 10) UNSIGNED NOT NULL DEFAULT '100.0000000000',
  `created`             INT(11) UNSIGNED        NOT NULL,
  `lastReturn`          INT(11) UNSIGNED        NOT NULL DEFAULT '0',
  `isWW`                TINYINT(1) UNSIGNED     NOT NULL DEFAULT '0',
  `isFarm`              TINYINT(1) UNSIGNED     NOT NULL DEFAULT '0',
  `isArtifact`          TINYINT(1) UNSIGNED     NOT NULL DEFAULT '0',
  `hidden`              TINYINT(1) UNSIGNED     NOT NULL DEFAULT '0',
  `evasion`             TINYINT(1) UNSIGNED     NOT NULL DEFAULT '0',
  `expandedfrom`        INT(6) UNSIGNED         NOT NULL,
  `d1TroopsVersion`     INT(11) UNSIGNED        NOT NULL DEFAULT '0',
  `d1MovementsVersion`  INT(11) UNSIGNED        NOT NULL DEFAULT '0',
  `lastVillageCheck`    INT(10) UNSIGNED        NOT NULL DEFAULT '1',
  `checker`             VARCHAR(50)             NULL     DEFAULT NULL,
  PRIMARY KEY (`kid`),
  KEY `owner` (`owner`),
  KEY `capital` (`capital`),
  KEY `isWW` (`isWW`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('vdata');
    }
};
