<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `demolition`
(
  `id`             INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `kid`            INT(6) UNSIGNED     NOT NULL,
  `building_field` TINYINT(2) UNSIGNED NOT NULL,
  `end_time`       INT(10) UNSIGNED    NOT NULL,
  `complete`       TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`, `kid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('demolition');
    }
};
