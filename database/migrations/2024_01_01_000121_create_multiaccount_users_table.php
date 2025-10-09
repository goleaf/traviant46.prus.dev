<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `multiaccount_users`
(
  `id`       INT(11)             NOT NULL AUTO_INCREMENT,
  `uid`      INT(10) UNSIGNED    NOT NULL,
  `data`     TEXT                NOT NULL,
  `priority` BIGINT(50) UNSIGNED NOT NULL,
  `time`     INT(10) UNSIGNED    NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('multiaccount_users');
    }
};
