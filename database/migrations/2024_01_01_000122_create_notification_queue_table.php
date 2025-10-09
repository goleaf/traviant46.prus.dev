<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `notificationQueue`
(
  `id`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `message` TEXT             NOT NULL,
  `time`    INT(10) UNSIGNED NOT NULL,
  `scheduled_at` TIMESTAMP   GENERATED ALWAYS AS (FROM_UNIXTIME(`time`)) STORED,
  PRIMARY KEY (`id`),
  KEY `scheduled_at` (`scheduled_at`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('notificationQueue');
    }
};
