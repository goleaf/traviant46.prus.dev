<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `ali_invite`
(
  `id`       INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_uid` INT(11) UNSIGNED NOT NULL,
  `aid`      INT(11) UNSIGNED NOT NULL,
  `uid`      INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `uid` (`uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ali_invite');
    }
};
