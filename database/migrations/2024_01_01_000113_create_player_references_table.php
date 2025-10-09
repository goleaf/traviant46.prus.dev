<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `player_references`
(
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_uid`     INT(11) UNSIGNED NOT NULL,
  `uid`         INT(11) UNSIGNED NOT NULL,
  `rewardGiven` TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `findById` (`uid`, `ref_uid`),
  KEY `rewardGiven` (`rewardGiven`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('player_references');
    }
};
