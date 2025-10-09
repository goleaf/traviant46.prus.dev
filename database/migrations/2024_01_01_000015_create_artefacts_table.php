<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `artefacts`
(
  `id`          INT(11) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `uid`         INT(11) UNSIGNED     NOT NULL,
  `kid`         INT(6) UNSIGNED      NOT NULL,
  `release_kid` INT(6) UNSIGNED      NOT NULL DEFAULT '0',
  `type`        SMALLINT(3) UNSIGNED NOT NULL,
  `size`        TINYINT(1) UNSIGNED  NOT NULL,
  `conquered`   INT(11) UNSIGNED     NOT NULL,
  `lastupdate`  INT(10) UNSIGNED     NOT NULL DEFAULT '0',
  `num`         SMALLINT(3)          NOT NULL,
  `effecttype`  SMALLINT(2)          NOT NULL,
  `effect`      DOUBLE               NOT NULL,
  `aoe`         INT(10)              NOT NULL,
  `status`      TINYINT(1)           NOT NULL DEFAULT '1',
  `active`      TINYINT(1)           NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY (`kid`),
  KEY (`size`),
  KEY (`conquered`),
  KEY (`status`),
  KEY (`type`),
  KEY (`effecttype`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('artefacts');
    }
};
