<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `alliance_notification`
(
  `id`     INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `aid`    INT(11) UNSIGNED    NOT NULL,
  `to_uid` INT(11) UNSIGNED    NOT NULL,
  `type`   TINYINT(1) UNSIGNED NOT NULL,
  `sent_at` TIMESTAMP          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `aid_to_uid_type_sent_at` (`aid`, `to_uid`, `type`, `sent_at`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('alliance_notification');
    }
};
