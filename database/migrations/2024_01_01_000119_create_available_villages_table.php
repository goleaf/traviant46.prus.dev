<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `available_villages`
(
  `kid`       INT(6) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `fieldtype` DOUBLE              NOT NULL,
  `r`         DOUBLE              NOT NULL,
  `angle`     DOUBLE              NOT NULL,
  `rand`      INT(10) UNSIGNED    NOT NULL,
  `occupied`  TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`kid`),
  KEY `angle` (`angle`),
  KEY `fieldtype` (`fieldtype`),
  KEY `r` (`r`),
  KEY `occupied` (`occupied`),
  KEY `rand` (`rand`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('available_villages');
    }
};
