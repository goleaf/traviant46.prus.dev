<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `forum_topic`
(
  `id`              INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `forumId`         INT(11) UNSIGNED    NOT NULL,
  `thread`          VARCHAR(35)         NOT NULL,
  `close`           TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `stick`           TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `SurveyStartTime` INT(10) UNSIGNED    NOT NULL,
  `Survey`          VARCHAR(60)         NOT NULL,
  `end_time`        INT(11) UNSIGNED    NOT NULL,
  PRIMARY KEY (`id`),
  KEY `forumId` (`forumId`, `thread`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_topic');
    }
};
