<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `buyGoldMessages`
(
  `id`           INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `uid`          INT(10) UNSIGNED    NOT NULL,
  `gold`         INT(10) UNSIGNED    NOT NULL,
  `type`         TINYINT(1) UNSIGNED NOT NULL,
  `trackingCode` VARCHAR(100)        NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('buyGoldMessages');
    }
};
