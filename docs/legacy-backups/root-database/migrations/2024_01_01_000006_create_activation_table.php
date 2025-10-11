<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `activation`
(
  `id`       INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`     VARCHAR(15)      NOT NULL,
  `password` VARCHAR(40)      NOT NULL,
  `email`    VARCHAR(90)      NULL     DEFAULT '',
  `token`    VARCHAR(32)      NOT NULL,
  `refUid`   INT(11)          NOT NULL,
  `time`     INT UNSIGNED     NOT NULL DEFAULT '0',
  `reminded` TINYINT UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `email` (`email`),
  KEY `token` (`token`),
  KEY `reminded` (`reminded`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );

        Schema::table('activation', function (Blueprint $table): void {
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activation');
    }
};
