<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `building_upgrade`
(
  `id`             INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `kid`            INT(6) UNSIGNED     NOT NULL,
  `building_field` TINYINT(2) UNSIGNED NOT NULL,
  `isMaster`       TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `start_time`     INT(10)             NOT NULL,
  `commence`       INT(11)             NOT NULL,
  PRIMARY KEY (`id`),
  KEY (`building_field`, `isMaster`, `commence`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('building_upgrade');
    }
};
