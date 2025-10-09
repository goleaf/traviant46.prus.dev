<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `infobox_read`
(
  `id`     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `infoId` INT(11) UNSIGNED NOT NULL,
  `uid`    INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `infoId` (`infoId`, `uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('infobox_read');
    }
};
