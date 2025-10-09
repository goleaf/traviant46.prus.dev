<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `multiaccount_log`
(
  `id`     INT(11) NOT NULL AUTO_INCREMENT,
  `uid`    INT(11) NOT NULL,
  `to_uid` INT(11) NOT NULL,
  `type`   INT(11) NOT NULL,
  `time`   INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `to_uid` (`to_uid`),
  KEY `uid` (`uid`),
  KEY `time` (`time`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('multiaccount_log');
    }
};
