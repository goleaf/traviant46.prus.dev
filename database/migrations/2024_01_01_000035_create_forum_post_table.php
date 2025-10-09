<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `forum_post`
(
  `id`      INT(11)             NOT NULL AUTO_INCREMENT,
  `aid`     INT(11) UNSIGNED    NOT NULL,
  `uid`     INT(11) UNSIGNED    NOT NULL,
  `forumId` INT(11) UNSIGNED    NOT NULL,
  `topicId` INT(11) UNSIGNED    NOT NULL,
  `post`    MEDIUMTEXT          NOT NULL,
  `time`    INT(10) UNSIGNED    NOT NULL,
  `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `aid` (`time`),
  KEY `uid` (`time`),
  KEY `time` (`time`),
  KEY `forumId` (`forumId`),
  KEY `topicId` (`topicId`),
  KEY `deleted` (`deleted`),
  FULLTEXT KEY `post_content` (`post`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_post');
    }
};
