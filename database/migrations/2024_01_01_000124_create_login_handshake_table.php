<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `login_handshake`
(
  `id`       INT(11)          NOT NULL AUTO_INCREMENT,
  `uid`      INT(10) UNSIGNED NOT NULL,
  `token`    VARCHAR(255)     NOT NULL,
  `isSitter` TINYINT(1)       NOT NULL DEFAULT '0',
  `time`     INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `token` (`token`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('login_handshake');
    }
};
