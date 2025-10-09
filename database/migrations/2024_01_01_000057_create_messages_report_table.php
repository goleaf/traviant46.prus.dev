<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `messages_report`
(
  `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid`          INT(11) UNSIGNED NOT NULL,
  `reported_uid` INT(11) UNSIGNED NOT NULL,
  `message_id`   INT(11) UNSIGNED NOT NULL,
  `type`         VARCHAR(255)     NOT NULL,
  `time`         INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY (`time`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('messages_report');
    }
};
