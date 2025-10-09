<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `activation_progress`
(
  `id`             INT(11)          NOT NULL AUTO_INCREMENT,
  `uid`            INT(10) UNSIGNED NOT NULL,
  `email`          VARCHAR(255)     NOT NULL,
  `activationCode` VARCHAR(30)      NOT NULL,
  `time`           INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`, `email`, `activationCode`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('activation_progress');
    }
};
