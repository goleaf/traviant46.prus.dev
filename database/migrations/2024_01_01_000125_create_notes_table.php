<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `notes`
(
  `id`        INT(11)          NOT NULL AUTO_INCREMENT,
  `uid`       INT(10) UNSIGNED NOT NULL,
  `to_uid`    INT(10) UNSIGNED NOT NULL,
  `note_text` TEXT,
  PRIMARY KEY (`id`),
  KEY `search` (`uid`, `to_uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
