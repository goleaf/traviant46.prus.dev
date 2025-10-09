<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `autoExtend`
(
  `id`          INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `uid`         INT(11) UNSIGNED    NOT NULL,
  `type`        TINYINT(1) UNSIGNED NOT NULL,
  `commence`    INT(10) UNSIGNED    NOT NULL,
  `lastChecked` INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `enabled`     TINYINT(1) UNSIGNED NOT NULL,
  `finished`    TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY (`uid`),
  KEY (`commence`, `enabled`, `finished`),
  KEY (`type`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('autoExtend');
    }
};
