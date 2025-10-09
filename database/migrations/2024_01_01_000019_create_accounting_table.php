<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `accounting`
(
  `id`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid`     INT(11) UNSIGNED NOT NULL,
  `cause`   VARCHAR(100)     NOT NULL,
  `reserve` INT(10)          NOT NULL,
  `balance` INT(10) UNSIGNED NOT NULL,
  `time`    INT(10) UNSIGNED NOT NULL,
  `recorded_at` TIMESTAMP    GENERATED ALWAYS AS (FROM_UNIXTIME(`time`)) STORED,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`, `balance`, `time`),
  KEY `recorded_at` (`recorded_at`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting');
    }
};
