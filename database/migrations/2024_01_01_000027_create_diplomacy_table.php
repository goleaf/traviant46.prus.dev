<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `diplomacy`
(
  `id`       INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `aid1`     INT(10) UNSIGNED    NOT NULL,
  `aid2`     INT(10) UNSIGNED    NOT NULL,
  `type`     TINYINT(1) UNSIGNED NOT NULL,
  `accepted` INT(1)              NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY (`aid1`, `aid2`, `type`, `accepted`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('diplomacy');
    }
};
