<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `farmlist_last_reports`
(
  `id`   INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `uid`  INT(11) UNSIGNED    NOT NULL,
  `kid`  INT(11) UNSIGNED    NOT NULL,
  `report_id`  INT(11) UNSIGNED    NOT NULL,
  PRIMARY KEY (`id`),
  KEY (`uid`, `kid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('farmlist_last_reports');
    }
};
