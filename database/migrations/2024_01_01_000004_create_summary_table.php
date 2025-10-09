<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `summary`
(
  `id`                        INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `players_count`             INT(11)          NOT NULL DEFAULT '0',
  `roman_players_count`       INT(11)          NOT NULL DEFAULT '0',
  `teuton_players_count`      INT(11)          NOT NULL DEFAULT '0',
  `gaul_players_count`        INT(11)          NOT NULL DEFAULT '0',
  `egyptians_players_count`   INT(11)          NOT NULL DEFAULT '0',
  `huns_players_count`        INT(11)          NOT NULL DEFAULT '0',
  `first_village_player_name` VARCHAR(255)     NULL     DEFAULT NULL,
  `first_village_time`        INT(11)          NOT NULL DEFAULT '0',
  `first_art_player_name`     VARCHAR(255)     NULL     DEFAULT NULL,
  `first_art_time`            INT(11)          NOT NULL DEFAULT '0',
  `first_ww_plan_player_name` VARCHAR(255)     NULL     DEFAULT NULL,
  `first_ww_plan_time`        INT(11)          NOT NULL DEFAULT '0',
  `first_ww_player_name`      VARCHAR(255)     NULL     DEFAULT NULL,
  `first_ww_time`             INT(11)          NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 2;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('summary');
    }
};
