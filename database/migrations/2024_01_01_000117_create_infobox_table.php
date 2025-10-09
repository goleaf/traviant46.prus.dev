<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `infobox`
(
  `id`         INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `forAll`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `uid`        INT(11) UNSIGNED    NOT NULL,
  `type`       TINYINT(2) UNSIGNED NOT NULL DEFAULT '0',
  `params`     TEXT                NOT NULL,
  `readStatus` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `del`        TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `show_from_at` TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `show_to_at`   TIMESTAMP         NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`, `forAll`, `readStatus`, `del`, `show_from_at`, `show_to_at`),
  KEY `visibility_window` (`show_from_at`, `show_to_at`),
  KEY `type` (`type`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('infobox');
    }
};
