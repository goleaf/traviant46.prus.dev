<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `forum_vote`
(
  `id`      INT(11) NOT NULL AUTO_INCREMENT,
  `uid`     INT(11) NOT NULL,
  `topicId` INT(11) NOT NULL,
  `value`   INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`, `topicId`, `value`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_vote');
    }
};
