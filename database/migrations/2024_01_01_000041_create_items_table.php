<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `items`
(
  `id`      INT(11) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `uid`     INT(11) UNSIGNED     NOT NULL,
  `btype`   TINYINT(2) UNSIGNED  NOT NULL,
  `type`    SMALLINT(3) UNSIGNED NOT NULL,
  `num`     BIGINT(100) UNSIGNED NOT NULL,
  `placeId` INT(11) UNSIGNED     NOT NULL,
  `proc`    TINYINT(1) UNSIGNED  NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY (`uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
