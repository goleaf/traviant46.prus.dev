<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `farmlist`
(
  `id`       INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kid`      INT(10) UNSIGNED NOT NULL,
  `owner`    INT(10) UNSIGNED NOT NULL,
  `name`     VARCHAR(45)      NOT NULL,
  `auto`     TINYINT(1)       NOT NULL DEFAULT '0',
  `lastRaid` INT(11)          NOT NULL DEFAULT '0',
  `randSec`  INT(11)          NOT NULL DEFAULT '30',
  PRIMARY KEY (`id`),
  KEY (`kid`, `owner`, `name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('farmlist');
    }
};
