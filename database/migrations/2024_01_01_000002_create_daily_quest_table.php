<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `daily_quest`
(
  `uid`                   INT(10) UNSIGNED     NOT NULL,
  `qst1`                  TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `qst2`                  TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `qst3`                  TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `qst4`                  TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `qst5`                  TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `qst6`                  TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `qst7`                  TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `qst8`                  TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `qst9`                  TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `qst10`                 TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `qst11`                 TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  `alliance_contribution` BIGINT(255) UNSIGNED NOT NULL DEFAULT '0',
  `reward1Type`           TINYINT(1) UNSIGNED           DEFAULT '0',
  `reward1Done`           TINYINT(1) UNSIGNED           DEFAULT '0',
  `reward2Type`           TINYINT(1) UNSIGNED           DEFAULT '0',
  `reward2Done`           TINYINT(1) UNSIGNED           DEFAULT '0',
  `reward3Type`           TINYINT(1) UNSIGNED           DEFAULT '0',
  `reward3Done`           TINYINT(1) UNSIGNED           DEFAULT '0',
  `reward4Type`           TINYINT(1) UNSIGNED           DEFAULT '0',
  `reward4Done`           TINYINT(1) UNSIGNED           DEFAULT '0',
  PRIMARY KEY (`uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_quest');
    }
};
