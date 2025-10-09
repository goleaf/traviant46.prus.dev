<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `banQueue`
(
  `id`     INT(11)                 NOT NULL AUTO_INCREMENT,
  `uid`    INT(11) DEFAULT NULL,
  `reason` VARCHAR(100)
             CHARACTER SET utf8mb4 NOT NULL,
  `time`   INT(11) DEFAULT NULL,
  `end`    INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('banQueue');
    }
};
