<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `inventory`
(
  `uid`                INT(11) UNSIGNED NOT NULL,
  `helmet`             INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `body`               INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `leftHand`           INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `rightHand`          INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `shoes`              INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `horse`              INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `bag`                INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `lastupdate`         INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `lastWaterBucketUse` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
