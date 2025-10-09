<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `odelete`
(
  `id`       INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kid`      INT(6) UNSIGNED  NOT NULL,
  `oid`      INT(6) UNSIGNED  NOT NULL,
  `end_time` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `kid` (`oid`),
  KEY (`end_time`, `oid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('odelete');
    }
};
