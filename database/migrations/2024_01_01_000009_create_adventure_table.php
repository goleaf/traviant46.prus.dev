<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `adventure`
(
  `id`   INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `uid`  INT(11)             NOT NULL,
  `kid`  INT(6) UNSIGNED     NOT NULL,
  `dif`  TINYINT(1)          NOT NULL,
  `available_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end`  TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `kid` (`kid`),
  KEY `available_at` (`available_at`),
  KEY `end` (`end`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('adventure');
    }
};
