<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `mdata`
(
  `id`              INT(11) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `uid`             INT(11) UNSIGNED     NOT NULL,
  `to_uid`          INT(11) UNSIGNED     NOT NULL,
  `topic`           VARCHAR(100)         NOT NULL,
  `message`         MEDIUMTEXT           NOT NULL,
  `viewed`          TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `archived`        TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `delete_receiver` SMALLINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `delete_sender`   SMALLINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `reported`        TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `md5_checksum`    VARCHAR(32)          NOT NULL DEFAULT '',
  `mode`            TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `time`            INT(10) UNSIGNED     NOT NULL DEFAULT '0',
  `autoType`        TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `isAlliance`      TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY (`uid`),
  KEY (`to_uid`),
  KEY `search` (`uid`, `to_uid`, `viewed`, `delete_receiver`),
  FULLTEXT KEY `message_content` (`message`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('mdata');
    }
};
