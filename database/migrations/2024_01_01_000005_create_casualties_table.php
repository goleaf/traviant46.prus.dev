<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `casualties`
(
  `id`         INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `attacks`    INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `casualties` BIGINT(50) UNSIGNED NOT NULL DEFAULT '0',
  `time`       INT(10) UNSIGNED    NOT NULL,
  PRIMARY KEY (`id`),
  KEY `time` (`time`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('casualties');
    }
};
