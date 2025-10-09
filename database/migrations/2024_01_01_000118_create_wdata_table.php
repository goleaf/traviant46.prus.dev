<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `wdata`
(
  `id`           INT(6) UNSIGNED      NOT NULL AUTO_INCREMENT,
  `x`            SMALLINT(4)          NOT NULL,
  `y`            SMALLINT(4)          NOT NULL,
  `fieldtype`    TINYINT(2) UNSIGNED  NOT NULL,
  `oasistype`    TINYINT(2) UNSIGNED  NOT NULL,
  `landscape`    TINYINT(2) UNSIGNED  NOT NULL,
  `crop_percent` SMALLINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `occupied`     TINYINT(1)           NOT NULL,
  `map`          VARCHAR(50)          NOT NULL DEFAULT '||=||',
  PRIMARY KEY (`id`),
  KEY `crop_percent` (`crop_percent`),
  KEY `fieldtype` (`fieldtype`),
  KEY `oasistype` (`oasistype`),
  KEY `occupied` (`occupied`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('wdata');
    }
};
