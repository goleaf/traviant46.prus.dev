<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `medal`
(
  `id`       INT(11) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `uid`      INT(11) UNSIGNED     NOT NULL,
  `category` TINYINT(2) UNSIGNED  NOT NULL,
  `week`     SMALLINT(3) UNSIGNED NOT NULL,
  `rank`     TINYINT(2) UNSIGNED  NOT NULL,
  `points`   VARCHAR(15)          NOT NULL,
  `img`      VARCHAR(10)          NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `rank` (`rank`),
  KEY `category` (`category`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('medal');
    }
};
