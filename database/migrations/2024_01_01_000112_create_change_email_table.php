<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `changeEmail`
(
  `uid`   INT(11) UNSIGNED NOT NULL,
  `email` VARCHAR(99)      NOT NULL,
  `code1` VARCHAR(5)       NOT NULL,
  `code2` VARCHAR(5)       NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `email` (`email`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('changeEmail');
    }
};
