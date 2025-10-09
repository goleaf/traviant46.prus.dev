<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `alidata`
(
  `id`                           INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`                         VARCHAR(25)         NOT NULL,
  `tag`                          VARCHAR(8)          NOT NULL,
  `desc1`                        TEXT                         DEFAULT NULL,
  `desc2`                        TEXT                         DEFAULT NULL,
  `info1`                        TEXT                         DEFAULT NULL,
  `info2`                        TEXT                         DEFAULT NULL,
  `forumLink`                    VARCHAR(200)                 DEFAULT NULL,
  `max`                          TINYINT(2) UNSIGNED NOT NULL DEFAULT '0',
  `total_attack_points`          BIGINT(255)         NOT NULL DEFAULT '0',
  `total_defense_points`         BIGINT(255)         NOT NULL DEFAULT '0',
  `week_attack_points`           BIGINT(255)         NOT NULL DEFAULT '0',
  `week_defense_points`          BIGINT(255)         NOT NULL DEFAULT '0',
  `week_robber_points`           BIGINT(255)         NOT NULL DEFAULT '0',
  `week_pop_changes`             BIGINT(255)         NOT NULL DEFAULT '0',
  `oldPop`                       BIGINT(255)         NOT NULL DEFAULT '0',
  `training_bonus_level`         TINYINT(1)          NOT NULL DEFAULT '0',
  `training_bonus_contributions` BIGINT(255)         NOT NULL DEFAULT '0',
  `armor_bonus_level`            TINYINT(1)          NOT NULL DEFAULT '0',
  `armor_bonus_contributions`    BIGINT(255)         NOT NULL DEFAULT '0',
  `cp_bonus_level`               TINYINT(1)          NOT NULL DEFAULT '0',
  `cp_bonus_contributions`       BIGINT(255)         NOT NULL DEFAULT '0',
  `trade_bonus_level`            TINYINT(1)          NOT NULL DEFAULT '0',
  `trade_bonus_contributions`    BIGINT(255)         NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY (`tag`, `name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('alidata');
    }
};
