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
  `recorded_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `uid_balance_recorded_at` (`uid`, `balance`, `recorded_at`)
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
