<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `friendlist`
(
  `id`       INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `uid`      INT(11) UNSIGNED    NOT NULL,
  `to_uid`   INT(11) UNSIGNED    NOT NULL,
  `accepted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`, `to_uid`, `accepted`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('friendlist');
    }
};
