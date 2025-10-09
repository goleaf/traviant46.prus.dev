<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `training`
(
  `id`            INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `kid`           INT(6) UNSIGNED     NOT NULL,
  `nr`            TINYINT(2) UNSIGNED NOT NULL,
  `num`           BIGINT(50) UNSIGNED NOT NULL,
  `item_id`       TINYINT(2) UNSIGNED NOT NULL,
  `training_time` BIGINT(25) UNSIGNED NOT NULL,
  `commence`      BIGINT(25) UNSIGNED NOT NULL,
  `end_time`      BIGINT(25) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `kid` (`kid`),
  KEY `item_id` (`item_id`),
  KEY `commence` (`commence`),
  KEY `nr` (`nr`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('training');
    }
};
