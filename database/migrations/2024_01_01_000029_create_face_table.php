<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `face`
(
  `uid`         INT(10) UNSIGNED NOT NULL,
  `headProfile` SMALLINT(2)      NOT NULL,
  `hairColor`   SMALLINT(2)      NOT NULL,
  `hairStyle`   SMALLINT(2)      NOT NULL,
  `ears`        SMALLINT(2)      NOT NULL,
  `eyebrow`     SMALLINT(2)      NOT NULL,
  `eyes`        SMALLINT(2)      NOT NULL,
  `nose`        SMALLINT(2)      NOT NULL,
  `mouth`       SMALLINT(2)      NOT NULL,
  `beard`       SMALLINT(2)      NOT NULL,
  `gender`      VARCHAR(6)       NOT NULL DEFAULT 'male',
  `lastupdate`  INT(11)          NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('face');
    }
};
