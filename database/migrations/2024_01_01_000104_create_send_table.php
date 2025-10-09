<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `send`
(
  `id`       INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `kid`      INT(6) UNSIGNED     NOT NULL,
  `to_kid`   INT(6) UNSIGNED     NOT NULL,
  `wood`     BIGINT(50) UNSIGNED NOT NULL,
  `clay`     BIGINT(50) UNSIGNED NOT NULL,
  `iron`     BIGINT(50) UNSIGNED NOT NULL,
  `crop`     BIGINT(50) UNSIGNED NOT NULL,
  `x`        TINYINT(1) UNSIGNED NOT NULL,
  `mode`     TINYINT(1) UNSIGNED NOT NULL,
  `end_time` INT(10) UNSIGNED    NOT NULL,
  `arrives_at` TIMESTAMP         GENERATED ALWAYS AS (FROM_UNIXTIME(`end_time`)) STORED,
  PRIMARY KEY (`id`),
  KEY `end_time` (`end_time`),
  KEY `arrives_at` (`arrives_at`),
  KEY `kid` (`kid`),
  KEY `to_kid` (`to_kid`),
  KEY `mode` (`mode`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('send');
    }
};
