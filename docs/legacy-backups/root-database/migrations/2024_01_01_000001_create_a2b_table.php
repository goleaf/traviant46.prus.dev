<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `a2b`
(
  `id`                 BIGINT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `queued_at`          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestamp_checksum` VARCHAR(6)          NOT NULL,
  `to_kid`             INT(6) UNSIGNED     NOT NULL,
  `u1`                 BIGINT(50) UNSIGNED NOT NULL,
  `u2`                 BIGINT(50) UNSIGNED NOT NULL,
  `u3`                 BIGINT(50) UNSIGNED NOT NULL,
  `u4`                 BIGINT(50) UNSIGNED NOT NULL,
  `u5`                 BIGINT(50) UNSIGNED NOT NULL,
  `u6`                 BIGINT(50) UNSIGNED NOT NULL,
  `u7`                 BIGINT(50) UNSIGNED NOT NULL,
  `u8`                 BIGINT(50) UNSIGNED NOT NULL,
  `u9`                 BIGINT(50) UNSIGNED NOT NULL,
  `u10`                BIGINT(50) UNSIGNED NOT NULL,
  `u11`                TINYINT(1) UNSIGNED NOT NULL,
  `attack_type`        TINYINT(1) UNSIGNED NOT NULL,
  `redeployHero`       TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `queued_at_checksum` (`queued_at`, `timestamp_checksum`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('a2b');
    }
};
