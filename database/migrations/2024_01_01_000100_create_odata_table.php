<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `odata`
(
  `kid`                 INT(6) UNSIGNED         NOT NULL,
  `type`                TINYINT(2) UNSIGNED     NOT NULL,
  `did`                 INT(6) UNSIGNED         NOT NULL DEFAULT '0',
  `wood`                DOUBLE(50, 2)           NOT NULL,
  `iron`                DOUBLE(50, 2)           NOT NULL,
  `clay`                DOUBLE(50, 2)           NOT NULL,
  `crop`                DOUBLE(50, 2)           NOT NULL,
  `lasttrain`           INT(10) UNSIGNED        NOT NULL DEFAULT '0',
  `lastfarmed`          INT(11) UNSIGNED        NOT NULL DEFAULT '0',
  `last_loyalty_update` INT(10) UNSIGNED        NOT NULL DEFAULT '0',
  `lastmupdate`         BIGINT(15) UNSIGNED     NOT NULL,
  `conquered_time`      INT(10) UNSIGNED        NOT NULL DEFAULT '0',
  `loyalty`             DOUBLE(13, 10) UNSIGNED NOT NULL DEFAULT '100.0000000000',
  `owner`               INT(11) UNSIGNED        NOT NULL DEFAULT '0',
  PRIMARY KEY (`kid`),
  KEY `did` (`did`),
  KEY `type` (`type`),
  KEY `owner` (`owner`),
  KEY `last_loyalty_update` (`last_loyalty_update`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('odata');
    }
};
