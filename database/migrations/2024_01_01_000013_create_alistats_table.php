<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `alistats`
(
  `id`              INT(11) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `aid`             INT(11) UNSIGNED     NOT NULL,
  `killed_by`       BIGINT(255) UNSIGNED NOT NULL DEFAULT '0',
  `stolen_by`       BIGINT(255) UNSIGNED NOT NULL DEFAULT '0',
  `killed_of`       BIGINT(255) UNSIGNED NOT NULL DEFAULT '0',
  `stolen_of`       BIGINT(255) UNSIGNED NOT NULL DEFAULT '0',
  `total_off_point` BIGINT(255) UNSIGNED NOT NULL DEFAULT '0',
  `total_def_point` BIGINT(255) UNSIGNED NOT NULL DEFAULT '0',
  `time`            INT(10) UNSIGNED              DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `time` (`time`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('alistats');
    }
};
