<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `ndata`
(
  `id`            INT(11) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `aid`           INT(11) UNSIGNED     NOT NULL,
  `uid`           INT(11) UNSIGNED     NOT NULL,
  `isEnforcement` TINYINT(1) UNSIGNED  NOT NULL,
  `kid`           INT(6) UNSIGNED      NOT NULL,
  `to_kid`        INT(6) UNSIGNED      NOT NULL,
  `type`          TINYINT(2) UNSIGNED  NOT NULL,
  `bounty`        VARCHAR(255)         NOT NULL,
  `data`          TEXT                 NOT NULL,
  `time`          INT(10) UNSIGNED     NOT NULL,
  `private_key`   VARCHAR(12)          NOT NULL,
  `viewed`        TINYINT(1) UNSIGNED  NOT NULL,
  `archive`       TINYINT(1) UNSIGNED  NOT NULL,
  `deleted`       TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `losses`        SMALLINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `non_deletable` TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `uid` (`uid`),
  KEY `to_kid` (`to_kid`),
  KEY `deleted` (`deleted`),
  KEY `archive` (`archive`),
  KEY `type` (`type`),
  KEY `losses` (`losses`),
  KEY `viewed` (`viewed`),
  KEY `count` (`uid`, `archive`, `deleted`, `type`),
  KEY `search` (`uid`, `viewed`, `deleted`)
)
  ROW_FORMAT = COMPRESSED
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS `surrounding`;
CREATE TABLE IF NOT EXISTS `surrounding`
(
  `id`     INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `kid`    INT(11) UNSIGNED    NOT NULL,
  `x`      SMALLINT(4)         NOT NULL,
  `y`      SMALLINT(4)         NOT NULL,
  `type`   TINYINT(2) UNSIGNED NOT NULL,
  `params` TEXT,
  `time`   INT(10) UNSIGNED    NOT NULL,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `kid` (`kid`, `x`, `y`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ndata');
    }
};
