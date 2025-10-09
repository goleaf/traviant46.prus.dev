<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `allimedal`
(
  `id`       INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `aid`      INT(11) UNSIGNED    NOT NULL,
  `category` TINYINT(2) UNSIGNED NOT NULL,
  `week`     INT(3) UNSIGNED     NOT NULL,
  `rank`     TINYINT(2) UNSIGNED NOT NULL,
  `points`   VARCHAR(30)         NOT NULL,
  `img`      VARCHAR(10)         NOT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
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
        Schema::dropIfExists('allimedal');
    }
};
