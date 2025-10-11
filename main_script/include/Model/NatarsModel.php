<?php

namespace Model;

use Core\AI;
use Core\Config;
use Core\Database\DB;
use Core\Helper\Notification;
use Game\Buildings\AutoUpgrade;
use Game\Buildings\BuildingAction;
use Game\Formulas;
use function array_keys;
use Game\ResourcesHelper;
use function getGameElapsedSeconds;
use function getGameSpeed;
use function logError;
use function make_seed;
use const MAP_SIZE;
use function miliseconds;
use const PHP_EOL;
use function var_dump;

class NatarsModel
{
    public function runJobs()
    {
        $this->releaseArtifacts();
        $this->releaseWWConstructionPlans();
    }

    public function releaseArtifacts()
    {
        $db = DB::getInstance();
        $config = Config::getInstance();
        if ($config->dynamic->ArtifactsReleased) return;
        $ArtifactsReleased = $db->fetchScalar("SELECT ArtifactsReleased FROM config") == 1;
        if (!$ArtifactsReleased && time() >= $config->timers->ArtifactsReleaseTime) {
            $arts = new ArtefactsModel();
            $arts->releaseArtifacts();
            Notification::notify("Artifacts Released", "Game artifacts are now released.");
        }
    }

    public function releaseWWConstructionPlans()
    {
        $db = DB::getInstance();
        $config = Config::getInstance();
        if ($config->dynamic->WWPlansReleased) return;
        $WWPlansReleased = $db->fetchScalar("SELECT WWPlansReleased FROM config") == 1;
        if (!$WWPlansReleased && time() >= $config->timers->wwPlansReleaseTime) {
            $db->checkConnection(true);
            $arts = new ArtefactsModel();
            $arts->releaseWWPlans();
            Notification::notify("WWPlans Released", "Game WWPlans are now released.");
        }
    }

    public function attackNewVillage($kid)
    {
        if (!getGame("attackNewVillageInGreyArea")) {
            return;
        }
        $miliseconds = miliseconds();
        $multiplier = getGameSpeed() <= 10 ? 1 : ceil(getGameSpeed() / (isInstantFinishEnabled() ? 30 : 50));
        $move = new MovementsModel();
        $cap_kid = Formulas::xy2kid(0, 0);
        $wave_arr = [
            1  => [1 => 1000, 1250, 250, 0, 900, 600, 1000, 100, 0, 0, 0],
            2  => [1 => 3, 250, 19, 0, 4, 115, 226, 0, 0, 0, 0],
            3  => [1 => 75, 134, 35, 0, 143, 54, 159, 77, 0, 0, 0],
            4  => [1 => 211, 177, 18, 0, 137, 106, 205, 90, 0, 0, 0],
            5  => [1 => 13, 153, 45, 0, 97, 102, 187, 2, 0, 0, 0],
            6  => [1 => 186, 126, 8, 0, 17, 130, 181, 98, 0, 0, 0],
            7  => [1 => 59, 78, 11, 0, 24, 13, 172, 99, 0, 0, 0],
            8  => [1 => 139, 186, 57, 0, 85, 140, 115, 48, 0, 0, 0],
            9  => [1 => 137, 120, 59, 0, 33, 138, 99, 79, 0, 0, 0],
            10 => [1 => 170, 193, 9, 0, 77, 20, 150, 12, 0, 0, 0],
            11 => [1 => 138, 146, 8, 0, 209, 90, 236, 22, 0, 0, 0],
            12 => [1 => 122, 5, 47, 0, 93, 81, 235, 9, 0, 0, 0],
            13 => [1 => 183, 145, 5, 0, 23, 31, 129, 31, 0, 0, 0],
            14 => [1 => 25, 197, 46, 0, 138, 143, 60, 18, 0, 0, 0],
        ];
        foreach ($wave_arr as $waveNumber => $units) {
            if ($waveNumber > 11) continue;
            $units = array_map(function ($x) use ($multiplier) {
                return round($x * $multiplier);
            },
                $units);
            $time = max(24 * 3600 / getGameSpeed(), 600);
            $increase = 0;
            if ($waveNumber <= 3) {
                $increase = 0;
            } else if ($waveNumber <= 6) {
                $increase = 1;
            } else if ($waveNumber <= 9) {
                $increase = 2;
            } else if ($waveNumber <= 11) {
                $increase = 3;
            }
            $move->addMovement($cap_kid,
                $kid,
                5,
                $units,
                99,
                99,
                0,
                0,
                0,
                MovementsModel::ATTACKTYPE_NORMAL,
                $miliseconds,
                $miliseconds + (1000 * $time) + $increase);
        }
    }

    public function createFarmVillages()
    {
        mt_srand(make_seed());
        $register = new RegisterModel();
        $x = 0;
        $db = DB::getInstance();
        $count = ceil(Config::getProperty("farms", "bigFarmsCount") / 4);
        $sides = [1 => 'se', 'ne', 'sw', 'nw'];
        $r = 65;
        for ($z = 1; $z <= 4; ++$z) {
            $find = $register->generateNatarsVillageInSide($sides[$z], $count, $r);
            if (!empty($find)) {
                $db->query("UPDATE available_villages SET occupied=1 WHERE kid IN($find)");
                $kids_array = explode(",", $find);
                foreach ($kids_array as $kid) {
                    $register->createNewFarmVillage($kid, ++$x, true);
                }
            }
        }
        $count = ceil(Config::getProperty("farms", "smallFarmsCount") / 4);
        for ($z = 1; $z <= 4; ++$z) {
            $find = $register->generateNatarsVillageInSide($sides[$z], $count, $r);
            if (!empty($find)) {
                $db->query("UPDATE available_villages SET occupied=1 WHERE kid IN($find)");
                $kids_array = explode(",", $find);
                foreach ($kids_array as $kid) {
                    $register->createNewFarmVillage($kid, ++$x, false);
                }
            }
        }
    }

    public function VillageToPOP($kid, $targetPop)
    {
        $db = DB::getInstance();
        $try = 0;
        $vRow = $db->query("SELECT pop, capital, owner, isWW FROM vdata WHERE kid=$kid")->fetch_assoc();
        if ($vRow['isWW']) {
            return;
        }
        $race = $db->fetchScalar("SELECT race FROM users WHERE id={$vRow['owner']}");
        $AutoUpgrade = new AutoUpgrade($kid, $race);
        if ($vRow['capital']) {
            $AutoUpgrade->isCapital();
        }
        $pop = $vRow['pop'];
        while ($pop <= $targetPop && $try <= 20) {
            ++$try;
            $count = $targetPop - $pop;
            if ($count >= 500) {
                $count = 50;
            } else if ($count >= 250) {
                $count = 35;
            } else if ($count >= 100) {
                $count = 25;
            } else {
                $count = 15;
            }
            for ($i = 1; $i <= $count; ++$i) {
                $AutoUpgrade->upgrade();
            }
            /*if($pop >= 200) {
                $nr = 2;
            } else if($pop >= 400) {
                $nr = 3;
            } else {
                $nr = mt_rand(1, 8);
            }
            $base = pow(0.968, round($pop / 100) * (Config::getProperty("game", "speed") > 100 ? (100+Config::getProperty("game", "speed")/5) : Config::getProperty("game", "speed")));
            $ratio = 1 + (100 + $pop / 100);
            for($i = 1; $i <= $nr; ++$i) {
                $num = abs(round($base * ($ratio - round($nr / 3, 1))));
                $db->query("UPDATE units SET u{$i}=u{$i}+$num WHERE kid=".$kid);
            }*/
            $pop = $db->fetchScalar("SELECT pop FROM vdata WHERE kid=$kid");
        }
        ResourcesHelper::updateVillageResources($kid);
    }

    private function canRunNatars()
    {
        if (getGameElapsedSeconds() <= 0) return false;
        make_seed();
        return true;
    }

    public function handleNatarVillages()
    {
        if (!$this->canRunNatars()) return;
        $db = DB::getInstance();
        if (getGameSpeed() <= 100) {
            $interval = 300;
        } else if (getGameSpeed() <= 1000) {
            $interval = 200;
        } else {
            $interval = 100;
        }
        $checkInterval = 900;
        $limit = 10;
        $time = time() - $checkInterval;
        $now = time();
        $result = $db->query("SELECT kid, lastVillageCheck FROM vdata WHERE owner=1 AND isWW=0 AND isFarm=0 AND isArtifact=0 AND lastVillageCheck > 0 AND lastVillageCheck <= $time LIMIT $limit");
        while ($row = $result->fetch_assoc()) {
            $db->query("UPDATE vdata SET lastVillageCheck=$now WHERE kid={$row['kid']}");
            if ($row['lastVillageCheck'] <= 10) continue;
            $count = ceil(($now - $row['lastVillageCheck']) / $interval);
            AI::doSomethingRandom($row['kid'], $count);
        }
    }

    public function handleNatarExpansion()
    {
        if (!$this->canRunNatars()) return;
        if(getGameSpeed() > 50) return;
        if(getGameSpeed() == 1) return;
        $db = DB::getInstance();
        $register = new RegisterModel();
        $sides = [
            'ne' => [0, 90],
            'nw' => [90, 180],
            'se' => [270, 360],
            'sw' => [180, 270],
        ];
        foreach ($sides as $side => $angle) {
            $countNormalVillages = $db->query("SELECT COUNT(kid) FROM available_villages av WHERE occupied=1 AND (angle >= {$angle[0]} AND angle <= {$angle[1]}) AND (SELECT owner FROM vdata v WHERE v.kid=av.kid)>2")->fetch_row()[0];
            $countNatarsVillages = $db->query("SELECT COUNT(av.kid) FROM available_villages av, vdata v WHERE av.occupied=1 AND (av.angle >= {$angle[0]} AND av.angle <= {$angle[1]}) AND v.kid=av.kid AND v.owner=1 AND v.isWW=0 AND (SELECT COUNT(id) FROM artefacts WHERE kid=v.kid)=0")->fetch_row()[0];

            $natarsShouldBe = round($countNormalVillages / 2);
            $percent = max(0, getGameElapsedSeconds()) / (getGame('round_length_real') * 86400);
            $pop = $percent * 900;
            $twentyPercent = $pop * 0.15;
            if ($natarsShouldBe > $countNatarsVillages) {
                $newVillageCount = min((int)$natarsShouldBe - $countNatarsVillages, 500);
                for ($i = 1; $i <= $newVillageCount; ++$i) {
                    make_seed();
                    $r = [];

                    $r[0] = 10 + mt_rand(4, 25) * (MAP_SIZE / 400);
                    $r[1] = $r[0] + 5 + mt_rand(15, 200) * (MAP_SIZE / 400);

                    $conditions = [];
                    $conditions[] = 'occupied=0';
                    $conditions[] = "(angle >= {$angle[0]} AND angle <= {$angle[1]})";
                    $conditions[] = "(r >= {$r[0]} AND r <= {$r[1]})";

                    $nearby = '(SELECT COUNT(av2.kid) FROM available_villages av2, vdata v2 WHERE av2.occupied=1 AND ABS(av2.r-av.r) <= 8 AND ABS(av2.angle-av.angle) <= 8 AND v2.kid=av2.kid AND v2.owner>1)';
                    $nearbyNatars = '(SELECT COUNT(av2.kid) FROM available_villages av2, vdata v2 WHERE av2.occupied=1 AND ABS(av2.r-av.r) <= 10 AND ABS(av2.angle-av.angle) <= 8 AND v2.kid=av2.kid AND v2.owner=1 AND v2.isWW=0 AND (SELECT COUNT(id) FROM artefacts WHERE kid=v2.kid)=0)';

                    $conditions[] = "$nearby > 1";
                    $conditions[] = "$nearbyNatars < 3";

                    $village = $db->query("SELECT kid FROM available_villages av WHERE ".implode(" AND ", $conditions)." ORDER BY rand LIMIT 1");

                    if ($village->num_rows) {
                        $kid = $village->fetch_row()[0];
                        $register->createNewNatarVillage($kid);
                        $this->VillageToPOP($kid, mt_rand($pop - $twentyPercent, $pop + $twentyPercent));
                    }
                }
            }
        }
        /*$interval = 21600;
        $lastExpand = $db->fetchScalar("SELECT lastNatarsExpand FROM config");
        if ($lastExpand <= 100) {
            $lastExpand = time() - $interval;
        }
        if ($lastExpand > time() - $interval) {
            return;
        }
        $count = min(ceil((time() - $lastExpand) / $interval), 100);
        $db->query("UPDATE config SET lastNatarsExpand=" . ($lastExpand + ($count * $interval)));
        $find = $register->generateNatarsVillage($count);
        if (!empty($find)) {
            $villages = explode(",", $find);
            $db->query("UPDATE available_villages SET occupied=1 WHERE kid IN($find)");
            foreach ($villages as $kid) {
                $register->createNewNatarVillage($kid);
            }
        }*/
    }

    public function handleNatarDefense()
    {
        if (!$this->canRunNatars()) {
            return;
        }

        $db = DB::getInstance();
        $speed = max(1, (int)getGameSpeed());
        $elapsedSeconds = max(0, (int)getGameElapsedSeconds());
        $roundLengthDays = (int)getGame('round_length_real');
        $roundLengthSeconds = $roundLengthDays > 0 ? $roundLengthDays * 86400 : 0;
        $progress = $roundLengthSeconds > 0 ? min(1, $elapsedSeconds / $roundLengthSeconds) : 0;

        $speedMultiplier = max(1, (int)ceil($speed / 5));
        $progressMultiplier = max(1, (int)ceil(1 + ($progress * 3)));

        $villages = $db->query('SELECT kid, isWW FROM vdata WHERE owner=1 AND isFarm=0 AND isArtifact=0');
        if ($villages === false) {
            return;
        }

        $wwMultiplier = 4;
        $baseInfantry = 1200;
        $baseCavalry = 600;

        while ($village = $villages->fetch_assoc()) {
            $kid = (int)$village['kid'];
            $isWW = (int)$village['isWW'] === 1;

            $multiplier = $speedMultiplier * $progressMultiplier * ($isWW ? $wwMultiplier : 1);

            $infantryTarget = (int)round($baseInfantry * $multiplier);
            $cavalryTarget = (int)round($baseCavalry * $multiplier);

            $targets = [
                'u1' => (int)round($infantryTarget * 1.2),
                'u2' => $infantryTarget,
                'u3' => (int)round($infantryTarget * 0.8),
                'u4' => (int)round($infantryTarget * 0.6),
                'u5' => (int)round($infantryTarget * 0.6),
                'u6' => $cavalryTarget,
                'u7' => (int)round($cavalryTarget * 0.9),
                'u8' => (int)round($cavalryTarget * 0.7),
                'u9' => (int)round($cavalryTarget * 0.5),
                'u10' => (int)round($cavalryTarget * 0.5),
            ];

            $db->query("INSERT IGNORE INTO units (kid, race) VALUES ($kid, 5)");

            $updates = ['race=5'];
            foreach ($targets as $column => $target) {
                $updates[] = sprintf('%s=GREATEST(%s,%d)', $column, $column, max(0, $target));
            }

            $db->query('UPDATE units SET ' . implode(',', $updates) . " WHERE kid=$kid");
        }
    }
}
