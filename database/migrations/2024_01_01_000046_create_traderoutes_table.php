<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `traderoutes`
(
  `id`         INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `kid`        INT(6) UNSIGNED     NOT NULL,
  `to_kid`     INT(6) UNSIGNED     NOT NULL,
  `r1`         BIGINT(50) UNSIGNED NOT NULL,
  `r2`         BIGINT(50) UNSIGNED NOT NULL,
  `r3`         BIGINT(50) UNSIGNED NOT NULL,
  `r4`         BIGINT(50) UNSIGNED NOT NULL,
  `enabled`    TINYINT(1) UNSIGNED NOT NULL,
  `start_hour` INT(10) UNSIGNED    NOT NULL,
  `times`      INT(10) UNSIGNED    NOT NULL,
  `time`       INT(10) UNSIGNED    NOT NULL,
  PRIMARY KEY (`id`),
  KEY `kid` (`kid`, `enabled`, `time`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('traderoutes');
    }
};
