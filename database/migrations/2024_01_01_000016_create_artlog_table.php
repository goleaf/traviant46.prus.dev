<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `artlog`
(
  `id`    INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `artId` INT(11) UNSIGNED NOT NULL,
  `uid`   INT(11)     DEFAULT NULL,
  `name`  VARCHAR(15) DEFAULT NULL,
  `kid`   INT(6) UNSIGNED  NOT NULL,
  `time`  INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `artId` (`artId`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('artlog');
    }
};
