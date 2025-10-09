<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `auction`
(
  `id`        INT(11) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `uid`       INT(11) UNSIGNED     NOT NULL,
  `btype`     TINYINT(2) UNSIGNED  NOT NULL,
  `type`      SMALLINT(3) UNSIGNED NOT NULL,
  `num`       BIGINT(100) UNSIGNED NOT NULL,
  `bids`      INT(11) UNSIGNED     NOT NULL DEFAULT '0',
  `silver`    INT(10) UNSIGNED     NOT NULL DEFAULT '0',
  `maxSilver` INT(10) UNSIGNED     NOT NULL DEFAULT '0',
  `activeUid` INT(10) UNSIGNED     NOT NULL DEFAULT '0',
  `activeId`  INT(11) UNSIGNED     NOT NULL DEFAULT '0',
  `secure_id` VARCHAR(100)         NOT NULL DEFAULT '',
  `time`      INT(10) UNSIGNED     NOT NULL,
  `finish`    TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `cancel`    TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY (`activeUid`),
  KEY (`activeId`),
  KEY `finish` (`finish`),
  KEY `cancel` (`cancel`),
  KEY `uid` (`uid`),
  KEY `secure_id` (`secure_id`),
  KEY `time` (`time`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('auction');
    }
};
