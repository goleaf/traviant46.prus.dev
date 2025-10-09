<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `links`
(
  `id`   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid`  INT(11) UNSIGNED NOT NULL,
  `name` VARCHAR(30)      NOT NULL,
  `url`  VARCHAR(255)     NOT NULL,
  `pos`  INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY (`uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
