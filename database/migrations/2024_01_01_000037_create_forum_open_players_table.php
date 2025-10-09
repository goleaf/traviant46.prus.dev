<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `forum_open_players`
(
  `id`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid`     INT(11) UNSIGNED NOT NULL,
  `forumId` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`, `forumId`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_open_players');
    }
};
