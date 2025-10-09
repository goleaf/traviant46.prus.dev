<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `config`
(
  `id`                          INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `startTime`                   INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `map_size`                    INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `worldUniqueId`               INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `patchVersion`                INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `installed`                   TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `automationState`             TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `truceFrom`                   INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `truceTo`                     INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `truceReasonId`               TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `startEmailsSent`             TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `startConfigurationDone`      TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `WWAlertSent`                 TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `installationTime`            INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `lastSystemCleanup`           INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `lastFakeAuction`             INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `lastNatarsExpand`            INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `lastDailyGold`               INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `lastDailyQuestReset`         INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `lastMedalsGiven`             INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `lastAllianceContributeReset` INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `ArtifactsReleased`           TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `WWPlansReleased`             TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `serverFinished`              TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `serverFinishTime`            INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `finishStatusSet`             TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `postServiceDone`             TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `fakeAccountProcess`          TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `maintenance`                 TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `delayTime`                   INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `lastBackup`                  INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `needsRestart`                TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `isRestore`                   TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `loginInfoTitle`              VARCHAR(100)        NOT NULL,
  `loginInfoHTML`               LONGTEXT            NOT NULL,
  `message`                     LONGTEXT            NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('config');
    }
};
