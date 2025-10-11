<?php

namespace Model;

use App\Services\AllianceService as BaseAllianceService;
use Core\Database\DB;
use Game\Map\Map;

class AllianceModel extends BaseAllianceService
{
    public function create(int $uid, string $name, string $tag): int
    {
        return $this->createAlliance($uid, $name, $tag);
    }

    public function invite(int $inviterUid, int $allianceId, int $targetUid): int
    {
        return $this->sendInvite($inviterUid, $allianceId, $targetUid);
    }

    public function kick(int $kickerUid, int $targetUid, int $allianceId): void
    {
        $this->kickPlayer($kickerUid, $targetUid, $allianceId);
    }

    public function manageDiplomacy(int $allianceId, array $payload): array
    {
        $db = DB::getInstance();
        $action = $payload['action'] ?? '';
        $now = time();

        switch ($action) {
            case 'offer':
                $type = (int)($payload['type'] ?? 0);
                $tag = isset($payload['tag']) ? trim((string)$payload['tag']) : '';
                if ($type < 1 || $type > 3) {
                    return ['success' => false, 'error' => 'Invalid diplomacy type.'];
                }
                if ($tag === '') {
                    return ['success' => false, 'error' => 'Alliance tag is required.'];
                }
                $tag = $db->real_escape_string(\sanitize_string($tag));
                $targetAllianceId = (int)$db->fetchScalar("SELECT id FROM alidata WHERE tag='{$tag}'");
                if (!$targetAllianceId) {
                    return ['success' => false, 'error' => 'Alliance not found.'];
                }
                if ($targetAllianceId === $allianceId) {
                    return ['success' => false, 'error' => 'Cannot create diplomacy with the same alliance.'];
                }
                $existing = (int)$db->fetchScalar("SELECT COUNT(id) FROM diplomacy WHERE (aid1={$allianceId} AND aid2={$targetAllianceId}) OR (aid1={$targetAllianceId} AND aid2={$allianceId})");
                if ($existing > 0) {
                    return ['success' => false, 'error' => 'A diplomacy offer already exists between these alliances.'];
                }
                $db->query("INSERT INTO diplomacy (aid1, aid2, type, accepted) VALUES ({$allianceId}, {$targetAllianceId}, {$type}, 0)");
                $diplomacyId = (int)$db->lastInsertId();
                $logType = $this->resolveDiplomacyLogType($type, 'offer');
                $this->addLog($allianceId, [$logType, $allianceId, $targetAllianceId], $now);
                $this->addLog($targetAllianceId, [$logType, $allianceId, $targetAllianceId], $now);

                return [
                    'success' => true,
                    'action' => 'offer',
                    'diplomacy_id' => $diplomacyId,
                    'target_alliance_id' => $targetAllianceId,
                ];

            case 'accept':
                $id = (int)($payload['id'] ?? 0);
                if ($id <= 0) {
                    return ['success' => false, 'error' => 'Diplomacy identifier is required.'];
                }
                $result = $db->query("SELECT * FROM diplomacy WHERE accepted=0 AND aid2={$allianceId} AND id={$id} LIMIT 1");
                if (!$result->num_rows) {
                    return ['success' => false, 'error' => 'Diplomacy offer not found.'];
                }
                $row = $result->fetch_assoc();
                $db->query("UPDATE diplomacy SET accepted=1 WHERE id={$id}");
                $logType = $this->resolveDiplomacyLogType((int)$row['type'], 'accept');
                $this->addLog((int)$row['aid1'], [$logType, $row['aid1'], $row['aid2']], $now);
                $this->addLog((int)$row['aid2'], [$logType, $row['aid1'], $row['aid2']], $now);
                Map::allianceDiplomacyCacheUpdate((int)$row['aid1'], (int)$row['aid2']);

                return [
                    'success' => true,
                    'action' => 'accept',
                    'diplomacy_id' => $id,
                ];

            case 'decline':
                $id = (int)($payload['id'] ?? 0);
                if ($id <= 0) {
                    return ['success' => false, 'error' => 'Diplomacy identifier is required.'];
                }
                $result = $db->query("SELECT * FROM diplomacy WHERE accepted=0 AND aid1={$allianceId} AND id={$id} LIMIT 1");
                if (!$result->num_rows) {
                    return ['success' => false, 'error' => 'Diplomacy offer not found.'];
                }
                $row = $result->fetch_assoc();
                $db->query("DELETE FROM diplomacy WHERE id={$id}");
                $logType = $this->resolveDiplomacyLogType((int)$row['type'], 'decline');
                $this->addLog((int)$row['aid1'], [$logType, $row['aid1'], $row['aid2']], $now);
                $this->addLog((int)$row['aid2'], [$logType, $row['aid1'], $row['aid2']], $now);
                Map::allianceDiplomacyCacheUpdate((int)$row['aid1'], (int)$row['aid2']);

                return [
                    'success' => true,
                    'action' => 'decline',
                    'diplomacy_id' => $id,
                ];

            case 'cancel':
                $id = (int)($payload['id'] ?? 0);
                if ($id <= 0) {
                    return ['success' => false, 'error' => 'Diplomacy identifier is required.'];
                }
                $result = $db->query("SELECT * FROM diplomacy WHERE accepted=1 AND (aid1={$allianceId} OR aid2={$allianceId}) AND id={$id} LIMIT 1");
                if (!$result->num_rows) {
                    return ['success' => false, 'error' => 'Diplomacy agreement not found.'];
                }
                $row = $result->fetch_assoc();
                $db->query("DELETE FROM diplomacy WHERE id={$id}");
                Map::allianceDiplomacyCacheUpdate((int)$row['aid1'], (int)$row['aid2']);

                return [
                    'success' => true,
                    'action' => 'cancel',
                    'diplomacy_id' => $id,
                ];

            default:
                return ['success' => false, 'error' => 'Unknown diplomacy action.'];
        }
    }

    private function resolveDiplomacyLogType(int $type, string $phase): int
    {
        $map = [
            'offer' => [
                1 => self::LOG_DIPLOMACY_CONF,
                2 => self::LOG_DIPLOMACY_NAP,
                3 => self::LOG_DIPLOMACY_WAR,
            ],
            'accept' => [
                1 => self::LOG_DIPLOMACY_CONF_ACCEPTED,
                2 => self::LOG_DIPLOMACY_NAP_ACCEPTED,
                3 => self::LOG_DIPLOMACY_WAR_ACCEPTED,
            ],
            'decline' => [
                1 => self::LOG_DIPLOMACY_CONF_REFUSE,
                2 => self::LOG_DIPLOMACY_NAP_REFUSE,
                3 => self::LOG_DIPLOMACY_WAR_REFUSE,
            ],
        ];

        return $map[$phase][$type] ?? self::LOG_DIPLOMACY_WAR;
    }
}
