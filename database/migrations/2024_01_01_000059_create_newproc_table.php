<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `newproc`
(
  `uid`  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cpw`  VARCHAR(30)      NOT NULL,
  `npw`  VARCHAR(45)      NOT NULL,
  `time` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('newproc');
    }
};
