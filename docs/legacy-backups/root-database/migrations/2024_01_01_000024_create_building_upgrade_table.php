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
  `started_at`     TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finishes_at`    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `started_at` (`started_at`),
  KEY `finishes_at` (`finishes_at`),
  KEY `building_field_master_finishes_at` (`building_field`, `isMaster`, `finishes_at`)
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
