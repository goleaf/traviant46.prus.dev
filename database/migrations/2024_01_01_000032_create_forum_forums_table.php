<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `forum_forums`
(
  `id`         INT(11)             NOT NULL AUTO_INCREMENT,
  `aid`        INT(11) UNSIGNED    NOT NULL,
  `name`       VARCHAR(20)         NOT NULL,
  `forum_desc` VARCHAR(38)         NOT NULL,
  `area`       TINYINT(1) UNSIGNED NOT NULL,
  `sitter`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `pos`        INT(6)              NOT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `area` (`area`),
  KEY `sitter` (`sitter`),
  KEY `pos` (`pos`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_forums');
    }
};
