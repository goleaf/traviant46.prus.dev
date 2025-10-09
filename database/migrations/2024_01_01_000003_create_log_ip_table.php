<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `log_ip`
(
  `id`   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid`  INT(11)          NOT NULL,
  `ip`   BIGINT(12)       NOT NULL,
  `time` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`, `time`, `ip`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('log_ip');
    }
};
