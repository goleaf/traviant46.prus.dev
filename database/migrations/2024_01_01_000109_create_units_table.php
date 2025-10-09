<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `units`
(
  `kid`  INT(6) UNSIGNED     NOT NULL,
  `race` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `u1`   BIGINT(50) UNSIGNED NOT NULL DEFAULT '0',
  `u2`   BIGINT(50) UNSIGNED NOT NULL DEFAULT '0',
  `u3`   BIGINT(50) UNSIGNED NOT NULL DEFAULT '0',
  `u4`   BIGINT(50) UNSIGNED NOT NULL DEFAULT '0',
  `u5`   BIGINT(50) UNSIGNED NOT NULL DEFAULT '0',
  `u6`   BIGINT(50) UNSIGNED NOT NULL DEFAULT '0',
  `u7`   BIGINT(50) UNSIGNED NOT NULL DEFAULT '0',
  `u8`   BIGINT(50) UNSIGNED NOT NULL DEFAULT '0',
  `u9`   BIGINT(50) UNSIGNED NOT NULL DEFAULT '0',
  `u10`  BIGINT(50) UNSIGNED NOT NULL DEFAULT '0',
  `u11`  TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `u99`  BIGINT(50) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`kid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
