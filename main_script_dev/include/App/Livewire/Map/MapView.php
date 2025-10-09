<?php

namespace App\Livewire\Map;

use Core\Config;
use Core\Database\DB;
use Core\Helper\TimezoneHelper;
use Core\Session;
use Game\Formulas;
use Model\AllianceModel;
use Model\KarteModel;
use Model\MapModel;
use Model\MovementsModel;
use function getButton;
use function json_encode;
use function logError;
use function nrToUnitId;
use function number_format_x;
use function T;

class MapView
{
    public function build(int $kid, int $zoomLevel, bool $fullscreen): array
    {
        $session = Session::getInstance();
        $mapModel = new MapModel();
        $karteModel = new KarteModel();

        $adventures = $this->getMapAdventureElements($karteModel, $session->getPlayerId());

        $flags = [];
        $playerMarks = [];
        $allianceMarks = [];
        $marks = $karteModel->getWholeMapMarks($session->getPlayerId(), $session->getAllianceId());
        while ($row = $marks->fetch_assoc()) {
            $this->procMapFlags($karteModel, $row, $flags, $playerMarks, $allianceMarks);
        }

        $elements = array_merge($flags, $adventures['elements']);

        $mapMarkSettings = $this->resolveMapMarkSettings($session);

        $xy = Formulas::kid2xy($kid);
        $blocks = [];
        $blocksArray = $mapModel->getNearMapBlocksWithVersion($xy['x'], $xy['y'], $zoomLevel);
        while ($row = $blocksArray->fetch_assoc()) {
            $blocks[$row['tx0']][$row['ty0']][$row['tx1']][$row['ty1']]['version'] = $row['version'];
        }
        ksort($blocks, SORT_NUMERIC);

        return [
            'hasPermission' => $session->hasAlliancePermission(AllianceModel::MANAGE_MARKS),
            'hasAlliance' => $session->getAllianceId(),
            'fullscreen' => $fullscreen,
            'coordinateSubmitButton' => $this->createCoordinateSubmitButton(),
            'smallMapEnabled' => Config::getProperty('settings', 'smallMapEnabled'),
            'hasPlus' => $session->hasPlus(),
            'hasClub' => $session->hasGoldClub(),
            'Map' => [
                'adventures' => $adventures['javascript'],
                'data' => json_encode([
                    'elements' => $elements,
                    'blocks' => $blocks,
                ]),
                'mapInitialPosition' => $xy,
                'zoomLevel' => $zoomLevel,
                'Marks' => [
                    'player' => [
                        'data' => json_encode($playerMarks),
                        'enabled' => $mapMarkSettings['ownMarks'],
                    ],
                    'alliance' => [
                        'data' => json_encode($allianceMarks),
                        'enabled' => $mapMarkSettings['allianceMarks'],
                    ],
                ],
            ],
        ];
    }

    public function getMovementAndReinforcementElements(): array
    {
        $result = ['elements' => []];
        $this->appendMovementAndReinforcementElements($result);
        return $result['elements'];
    }

    private function resolveMapMarkSettings(Session $session): array
    {
        $raw = explode(',', $session->getMapSettings());
        return [
            'ownMarks' => isset($raw[0]) && $raw[0] == 1,
            'allianceMarks' => isset($raw[1]) && $raw[1] == 1 && $session->getAllianceId(),
        ];
    }

    private function createCoordinateSubmitButton(): string
    {
        return getButton([
            'type' => 'submit',
            'value' => 'OK',
            'class' => 'green small',
        ], [
            'data' => ['value' => 'OK', 'class' => 'green small'],
        ], T('Global', 'General.ok'));
    }

    private function getMapAdventureElements(KarteModel $karteModel, int $playerId): array
    {
        $result = ['elements' => [], 'javascript' => ''];
        $i = $karteModel->getDoneAdventuresCount($playerId);
        $adventures = $karteModel->getAdventures($playerId);
        while ($adventure = $adventures->fetch_assoc()) {
            ++$i;
            $coordinates = Formulas::kid2xy($adventure['kid']);
            $title = T('map', 'Adventure') . ' ' . $i;
            $result['javascript'] .= "\t\t'a.atm{$i}': '{$title}',\n";
            $difficulty = T('map', $adventure['dif'] == 0 ? 'normal' : 'hard');
            $result['javascript'] .= "\t\t\t\t'a.ad{$i}': '{$difficulty}',\n";
            $result['elements'][] = [
                'position' => ['x' => $coordinates['x'], 'y' => $coordinates['y']],
                'symbols' => [[
                    'dataId' => 'adventure' . $adventure['id'],
                    'x' => $coordinates['x'],
                    'y' => $coordinates['y'],
                    'type' => 'adventure',
                    'parameters' => ['difficulty' => $adventure['dif'] + 1],
                    'title' => T('map', 'Adventure'),
                    'text' => "{a.atm{$i}} <br /> {a.ad} {a.ad{$i}}",
                ]],
            ];
        }
        $this->appendMovementAndReinforcementElements($result);
        return $result;
    }

    private function appendMovementAndReinforcementElements(array &$result): void
    {
        $db = DB::getInstance();
        $session = Session::getInstance();
        $kid = $session->getKid();
        if (!$kid) {
            logError('kid not found!? UID: %s', [$session->getPlayerId()]);
            return;
        }
        if ($session->hasPlus()) {
            $karteModel = new KarteModel();
            $allKids = $karteModel->getVillageOasesIds($kid);
            $allKids[] = $kid;
            $outGoing = $db->query("SELECT * FROM movement WHERE kid=$kid AND mode=0 AND attack_type NOT IN(5,6,7)");
            while ($row = $outGoing->fetch_assoc()) {
                $row['start_time_seconds'] = (int)ceil($row['start_time'] / 1000);
                $row['end_time_seconds'] = (int)ceil($row['end_time'] / 1000);
                $this->addMovementElement($result, $row);
            }
            $inComingGoing = $db->query('SELECT * FROM movement WHERE to_kid IN(' . implode(',', $allKids) . ') AND attack_type NOT IN(1,5,6,7)');
            while ($row = $inComingGoing->fetch_assoc()) {
                $row['start_time_seconds'] = (int)ceil($row['start_time'] / 1000);
                $row['end_time_seconds'] = (int)ceil($row['end_time'] / 1000);
                $this->addMovementElement($result, $row);
            }
        }
        $uid = $session->getPlayerId();
        $enforcements = $db->query("SELECT * FROM enforcement WHERE uid=$uid AND kid=$kid");
        while ($row = $enforcements->fetch_assoc()) {
            $this->addReinforcementElement($result, $row);
        }
    }

    private function addMovementElement(array &$result, array $row): void
    {
        $session = Session::getInstance();
        if ($row['kid'] != $session->getKid() && $row['mode'] == 0 && ($row['attack_type'] == 3 || $row['attack_type'] == 4)) {
            return;
        }
        if ($row['mode'] == 1) {
            $attackType = 'return';
        } elseif ($row['attack_type'] == MovementsModel::ATTACKTYPE_REINFORCEMENT) {
            $attackType = 'reinforcement';
        } else {
            $attackType = [
                MovementsModel::ATTACKTYPE_SPY => 'spy',
                MovementsModel::ATTACKTYPE_REINFORCEMENT => 'support',
                MovementsModel::ATTACKTYPE_NORMAL => 'attack',
                MovementsModel::ATTACKTYPE_RAID => 'raid',
            ][$row['attack_type']];
        }
        $taskType = $row['attack_type'] == MovementsModel::ATTACKTYPE_REINFORCEMENT ? 'reinforcement' : 'attack';
        if ($attackType == 'attack') {
            $title = '{k.sattack}';
        } elseif ($attackType == 'raid') {
            $title = '{k.sraid}';
        } elseif ($attackType == 'spy') {
            $title = '{k.sspy}';
        } elseif ($attackType == 'return') {
            $title = '{k.sreturn}';
        } else {
            $title = '{k.ssupport}';
        }
        $xy = Formulas::kid2xy($row['mode'] == 0 ? $row['to_kid'] : $row['kid']);
        $result['elements'][] = [
            'position' => [
                'x' => $xy['x'],
                'y' => $xy['y'],
            ],
            'symbols' => [[
                'dataId' => $taskType . $row['id'],
                'x' => $xy['x'],
                'y' => $xy['y'],
                'type' => $taskType,
                'parameters' => ['attackType' => $attackType, 'attackTime' => $row['start_time_seconds']],
                'title' => $title,
                'text' => '{k.arrival} ' . TimezoneHelper::autoDate($row['start_time_seconds'], true),
            ]],
        ];
    }

    private function addReinforcementElement(array &$result, array $row): void
    {
        $units = '';
        for ($i = 1; $i <= 11; ++$i) {
            if (!$row['u' . $i]) {
                continue;
            }
            if ($i == 11) {
                $unitId = 'hero';
            } else {
                $unitId = nrToUnitId($i, $row['race']);
            }
            $title = T('Troops', "$unitId.title");
            $numStr = number_format_x($row['u' . $i], 0);
            $units .= '<img class="unit u' . $unitId . '" src="img/x.gif" title="' . $title . '" alt="' . $title . '" /> ' . $numStr;
            if ($i <= 10 && $row['u' . ($i + 1)]) {
                $units .= '<br />';
            }
        }
        $xy = Formulas::kid2xy($row['to_kid']);
        $result['elements'][] = [
            'position' => [
                'x' => $xy['x'],
                'y' => $xy['y'],
            ],
            'symbols' => [[
                'type' => 'reinforcement',
                'dataId' => 'reinforcement' . $row['id'],
                'text' => $units,
                'x' => $xy['x'],
                'y' => $xy['y'],
            ]],
        ];
    }

    private function procMapFlags(KarteModel $karteModel, array $row, array &$flags, array &$playerMarks, array &$allianceMarks): void
    {
        $result = [];
        $result[$row['type'] == 2 ? 'index' : 'color'] = $row['color'];
        $result['text'] = $row['type'] == 0 ? $karteModel->getPlayerName($row['targetId']) : ($row['type'] == 1 ? $karteModel->getAllianceTag($row['targetId']) : $row['text']);
        $result['layer'] = $row['type'] == 0 ? 'player' : ($row['type'] == 1 ? 'alliance' : 'player');
        $result['dataId'] = $row['id'];
        if ($row['type'] == 2) {
            $result['plus'] = 0;
            $coordinates = Formulas::kid2xy($row['targetId']);
            $flags[] = [
                'position' => ['x' => $coordinates['x'], 'y' => $coordinates['y']],
                'symbols' => [array_merge($result, [
                    'type' => $row['type'] == 0 ? 'player' : ($row['type'] == 1 ? 'alliance' : 'flag'),
                    'kid' => $row['targetId'],
                    'x' => $coordinates['x'],
                    'y' => $coordinates['y'],
                ])],
            ];
            $result = array_merge($result, $coordinates);
        } else {
            $result['markId'] = $row['targetId'];
        }
        if (!$row['uid'] && $row['aid']) {
            $row['layer'] = 'alliance';
            $allianceMarks[] = $result;
        } else {
            $row['layer'] = 'player';
            $playerMarks[] = $result;
        }
    }
}
