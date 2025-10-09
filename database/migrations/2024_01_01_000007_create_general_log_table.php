<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `general_log`
(
  `id`       BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid`      INT(11)             NOT NULL,
  `type`     VARCHAR(50)         NOT NULL,
  `log_info` LONGTEXT            NOT NULL,
  `time`     INT(11)             NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `type` (`type`),
  KEY `time` (`time`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('general_log');
    }
};
