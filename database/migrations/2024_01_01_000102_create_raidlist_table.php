<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `raidlist`
(
  `id`       INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT,
  `lid`      INT(11) UNSIGNED      NOT NULL,
  `kid`      INT(6) UNSIGNED       NOT NULL,
  `distance` DOUBLE(4, 1) UNSIGNED NOT NULL,
  `u1`       BIGINT(50)            NOT NULL,
  `u2`       BIGINT(50)            NOT NULL,
  `u3`       BIGINT(50)            NOT NULL,
  `u4`       BIGINT(50)            NOT NULL,
  `u5`       BIGINT(50)            NOT NULL,
  `u6`       BIGINT(50)            NOT NULL,
  `u7`       BIGINT(50)            NOT NULL,
  `u8`       BIGINT(50)            NOT NULL,
  `u9`       BIGINT(50)            NOT NULL,
  `u10`      BIGINT(50)            NOT NULL,
  PRIMARY KEY (`id`),
  KEY (`lid`, `kid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('raidlist');
    }
};
