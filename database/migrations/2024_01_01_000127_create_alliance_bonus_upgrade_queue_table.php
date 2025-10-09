<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `alliance_bonus_upgrade_queue`
(
  `id`   INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `aid`  INT(11) UNSIGNED    NOT NULL,
  `type` TINYINT(1) UNSIGNED NOT NULL,
  `time` INT(11) UNSIGNED    NOT NULL,
  PRIMARY KEY (`id`),
  KEY (`aid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('alliance_bonus_upgrade_queue');
    }
};
