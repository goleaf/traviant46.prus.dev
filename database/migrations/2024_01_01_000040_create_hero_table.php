<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `hero`
(
  `uid`            INT(11) UNSIGNED        NOT NULL,
  `kid`            INT(6) UNSIGNED         NOT NULL,
  `exp`            BIGINT(255)             NOT NULL DEFAULT '0',
  `health`         DOUBLE(13, 10) UNSIGNED NOT NULL DEFAULT '100.0000000000',
  `itemHealth`     INT(11) UNSIGNED        NOT NULL DEFAULT '0',
  `power`          SMALLINT(3) UNSIGNED    NOT NULL DEFAULT '0',
  `offBonus`       SMALLINT(3) UNSIGNED    NOT NULL DEFAULT '0',
  `defBonus`       SMALLINT(3) UNSIGNED    NOT NULL DEFAULT '0',
  `production`     SMALLINT(3) UNSIGNED    NOT NULL DEFAULT '4',
  `productionType` SMALLINT(1) UNSIGNED    NOT NULL DEFAULT '0',
  `lastupdate`     INT(10) UNSIGNED        NOT NULL DEFAULT '0',
  `hide`           TINYINT(1) UNSIGNED     NOT NULL DEFAULT '1',
  PRIMARY KEY (`uid`),
  KEY `health` (`health`),
  KEY `lastupdate` (`lastupdate`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('hero');
    }
};
