<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `forum_edit`
(
  `id`     INT(11)          NOT NULL AUTO_INCREMENT,
  `uid`    INT(11) UNSIGNED NOT NULL,
  `postId` INT(11)          NOT NULL,
  `time`   INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `postId` (`postId`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_edit');
    }
};
