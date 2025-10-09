<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `mapflag`
(
  `id`       INT(11)              NOT NULL AUTO_INCREMENT,
  `aid`      INT(11) UNSIGNED     NOT NULL,
  `uid`      INT(11) UNSIGNED     NOT NULL,
  `targetId` INT(11) UNSIGNED     NOT NULL,
  `text`     VARCHAR(50)          NOT NULL,
  `color`    SMALLINT(2) UNSIGNED NOT NULL,
  `type`     SMALLINT(1) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`, `aid`, `type`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('mapflag');
    }
};
