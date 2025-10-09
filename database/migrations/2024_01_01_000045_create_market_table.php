<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `market`
(
  `id`        INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `aid`       INT(11) UNSIGNED    NOT NULL,
  `kid`       INT(6) UNSIGNED     NOT NULL,
  `x`         SMALLINT(4)         NOT NULL,
  `y`         SMALLINT(4)         NOT NULL,
  `rate`      DOUBLE UNSIGNED     NOT NULL,
  `needType`  TINYINT(1) UNSIGNED NOT NULL,
  `needValue` BIGINT(50) UNSIGNED NOT NULL,
  `giveType`  TINYINT(1) UNSIGNED NOT NULL,
  `giveValue` BIGINT(50)          NOT NULL,
  `maxtime`   INT(10) UNSIGNED    NOT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `rate` (`rate`),
  KEY `rType` (`needType`),
  KEY `giveType` (`giveType`),
  KEY `x` (`x`, `y`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('market');
    }
};
