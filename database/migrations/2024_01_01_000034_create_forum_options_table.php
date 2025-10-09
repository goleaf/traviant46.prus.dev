<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `forum_options`
(
  `id`          INT(11) UNSIGNED        NOT NULL AUTO_INCREMENT,
  `topicId`     INT(11) UNSIGNED        NOT NULL,
  `option_desc` VARCHAR(60)
                  CHARACTER SET utf8mb4 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `topicId` (`topicId`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_options');
    }
};
