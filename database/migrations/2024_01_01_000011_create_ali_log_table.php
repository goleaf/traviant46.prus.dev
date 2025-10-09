<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `ali_log`
(
  `id`   INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `aid`  INT(11)             NOT NULL,
  `type` TINYINT(1) UNSIGNED NOT NULL,
  `data` TEXT                NOT NULL,
  `time` INT(10) UNSIGNED    NOT NULL,
  PRIMARY KEY (`id`),
  KEY (`type`),
  KEY (`time`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ali_log');
    }
};
