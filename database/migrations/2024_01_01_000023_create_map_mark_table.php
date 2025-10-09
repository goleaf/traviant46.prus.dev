<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `map_mark`
(
  `id`        INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `uid`       INT(11)             NOT NULL,
  `tx0`       MEDIUMINT(4)        NOT NULL,
  `ty0`       MEDIUMINT(4)        NOT NULL,
  `tx1`       MEDIUMINT(4)        NOT NULL,
  `ty1`       MEDIUMINT(4)        NOT NULL,
  `zoomLevel` TINYINT(1) UNSIGNED NOT NULL,
  `version`   INT(11)             NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `tx0` (`uid`, `tx0`, `ty0`, `tx1`, `ty1`, `version`),
  KEY `zoomLevel` (`zoomLevel`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('map_mark');
    }
};
