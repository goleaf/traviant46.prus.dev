<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `bids`
(
  `id`        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid`       INT(11) UNSIGNED NOT NULL,
  `auctionId` INT(11) UNSIGNED NOT NULL,
  `outbid`    TINYINT(1)       NOT NULL DEFAULT '0',
  `del`       TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`, `auctionId`, `outbid`, `del`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
