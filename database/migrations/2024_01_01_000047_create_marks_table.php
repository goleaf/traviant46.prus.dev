<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `marks`
(
  `id`     INT(11)          NOT NULL AUTO_INCREMENT,
  `kid`    INT(6) UNSIGNED  NOT NULL,
  `map_id` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY (`kid`),
  KEY `map_id` (`map_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('marks');
    }
};
