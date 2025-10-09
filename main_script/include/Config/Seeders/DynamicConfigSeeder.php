<?php

namespace Config\Seeders;

use Core\Config;
use Core\Database\DB;

class DynamicConfigSeeder
{
    public static function seed(array $overrides = []): bool
    {
        $config = Config::getInstance();
        $db = DB::getInstance();
        $dynamic = property_exists($config, 'dynamic') && is_object($config->dynamic) ? $config->dynamic : (object)[];
        $defaults = [
            'startTime' => (int)($config->game->start_time ?? 0),
            'map_size' => defined('MAP_SIZE') ? (int)MAP_SIZE : 0,
            'worldUniqueId' => (int)($config->settings->worldUniqueId ?? 0),
            'patchVersion' => (int)($dynamic->patchVersion ?? 0),
            'installed' => (int)($dynamic->installed ?? 0),
            'automationState' => (int)($dynamic->automationState ?? 1),
            'truceFrom' => (int)($dynamic->truceFrom ?? 0),
            'truceTo' => (int)($dynamic->truceTo ?? 0),
            'truceReasonId' => (int)($dynamic->truceReasonId ?? 0),
            'startEmailsSent' => (int)($dynamic->startEmailsSent ?? 0),
            'startConfigurationDone' => (int)($dynamic->startConfigurationDone ?? 0),
            'WWAlertSent' => (int)($dynamic->WWAlertSent ?? 0),
            'installationTime' => (int)($dynamic->installationTime ?? 0),
            'lastSystemCleanup' => (int)($dynamic->lastSystemCleanup ?? 0),
            'lastFakeAuction' => (int)($dynamic->lastFakeAuction ?? 0),
            'lastNatarsExpand' => (int)($dynamic->lastNatarsExpand ?? 0),
            'lastDailyGold' => (int)($dynamic->lastDailyGold ?? 0),
            'lastDailyQuestReset' => (int)($dynamic->lastDailyQuestReset ?? 0),
            'lastMedalsGiven' => (int)($dynamic->lastMedalsGiven ?? 0),
            'lastAllianceContributeReset' => (int)($dynamic->lastAllianceContributeReset ?? 0),
            'ArtifactsReleased' => (int)($dynamic->ArtifactsReleased ?? 0),
            'WWPlansReleased' => (int)($dynamic->WWPlansReleased ?? 0),
            'serverFinished' => (int)($dynamic->serverFinished ?? 0),
            'serverFinishTime' => (int)($dynamic->serverFinishTime ?? 0),
            'finishStatusSet' => (int)($dynamic->finishStatusSet ?? 0),
            'postServiceDone' => (int)($dynamic->postServiceDone ?? 0),
            'fakeAccountProcess' => (int)($dynamic->fakeAccountProcess ?? 1),
            'maintenance' => (int)($dynamic->maintenance ?? 0),
            'delayTime' => (int)($dynamic->delayTime ?? 0),
            'lastBackup' => (int)($dynamic->lastBackup ?? 0),
            'needsRestart' => (int)($dynamic->needsRestart ?? 0),
            'isRestore' => (int)($dynamic->isRestore ?? 0),
            'loginInfoTitle' => (string)($dynamic->loginInfoTitle ?? ''),
            'loginInfoHTML' => (string)($dynamic->loginInfoHTML ?? ''),
            'message' => (string)($dynamic->message ?? ''),
        ];
        $data = array_merge($defaults, $overrides);
        $result = $db->query('SELECT id FROM config LIMIT 1');
        if ($result === false) {
            throw new \RuntimeException('Failed to fetch config row: ' . $db->mysqli->error);
        }
        $columns = array_keys($data);
        if ($result->num_rows) {
            $row = $result->fetch_assoc();
            $assignments = [];
            foreach ($columns as $column) {
                $value = $data[$column];
                if (is_bool($value)) {
                    $value = $value ? 1 : 0;
                }
                $assignments[] = sprintf("`%s`='%s'", $column, $db->mysqli->real_escape_string((string)$value));
            }
            $query = sprintf("UPDATE config SET %s WHERE id=%d", implode(',', $assignments), (int)$row['id']);
            $status = $db->query($query);
            if ($status === false) {
                throw new \RuntimeException('Failed to update config row: ' . $db->mysqli->error);
            }
            return (bool)$status;
        }
        $columnSql = '`' . implode('`,`', $columns) . '`';
        $values = [];
        foreach ($columns as $column) {
            $value = $data[$column];
            if (is_bool($value)) {
                $value = $value ? 1 : 0;
            }
            $values[] = "'" . $db->mysqli->real_escape_string((string)$value) . "'";
        }
        $query = sprintf("INSERT INTO config (%s) VALUES (%s)", $columnSql, implode(',', $values));
        $status = $db->query($query);
        if ($status === false) {
            throw new \RuntimeException('Failed to insert config row: ' . $db->mysqli->error);
        }
        return (bool)$status;
    }
}
