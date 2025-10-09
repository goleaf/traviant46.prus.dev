<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `tdata`
(
  `kid` INT(6) UNSIGNED     NOT NULL,
  `u2`  TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `u3`  TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `u4`  TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `u5`  TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `u6`  TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `u7`  TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `u8`  TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `u9`  TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`kid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('tdata');
    }
};
