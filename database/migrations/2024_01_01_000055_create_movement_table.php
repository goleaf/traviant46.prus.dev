<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `movement`
(
  `id`           INT(11) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `kid`          INT(6) UNSIGNED      NOT NULL,
  `to_kid`       INT(6) UNSIGNED      NOT NULL,
  `race`         TINYINT(1) UNSIGNED  NOT NULL,
  `u1`           BIGINT(50)           NOT NULL DEFAULT '0',
  `u2`           BIGINT(50)           NOT NULL DEFAULT '0',
  `u3`           BIGINT(50)           NOT NULL DEFAULT '0',
  `u4`           BIGINT(50)           NOT NULL DEFAULT '0',
  `u5`           BIGINT(50)           NOT NULL DEFAULT '0',
  `u6`           BIGINT(50)           NOT NULL DEFAULT '0',
  `u7`           BIGINT(50)           NOT NULL DEFAULT '0',
  `u8`           BIGINT(50)           NOT NULL DEFAULT '0',
  `u9`           BIGINT(50)           NOT NULL DEFAULT '0',
  `u10`          BIGINT(50)           NOT NULL DEFAULT '0',
  `u11`          SMALLINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `ctar1`        TINYINT(2) UNSIGNED  NOT NULL DEFAULT '0',
  `ctar2`        TINYINT(2) UNSIGNED  NOT NULL DEFAULT '0',
  `spyType`      TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `redeployHero` TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `mode`         TINYINT(1) UNSIGNED  NOT NULL,
  `attack_type`  TINYINT(1) UNSIGNED  NOT NULL,
  `start_time`   BIGINT(15) UNSIGNED  NOT NULL,
  `end_time`     BIGINT(15) UNSIGNED  NOT NULL,
  `data`         VARCHAR(255)         NOT NULL DEFAULT '',
  `markState`    TINYINT(1)           NOT NULL DEFAULT '0',
  `proc`         TINYINT(1)           NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `attack_type` (`attack_type`),
  KEY `kid` (`kid`),
  KEY `to_kid` (`to_kid`),
  KEY `u11` (`u11`),
  KEY `search` (`kid`, `to_kid`, `mode`, `attack_type`),
  KEY `end_time` (`end_time`),
  KEY `mode` (`mode`),
  KEY `proc` (`proc`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('movement');
    }
};
