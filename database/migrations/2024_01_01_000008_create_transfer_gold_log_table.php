<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `transfer_gold_log`
(
  `id`     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid`    INT(11)          NOT NULL,
  `to_uid` INT(11)          NOT NULL,
  `amount` VARCHAR(50)      NOT NULL,
  `time`   INT(11)          NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_gold_log');
    }
};
