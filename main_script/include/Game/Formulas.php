<?php

namespace Game;

use function array_values;
use Core\Config;
use function getCustom;
use function getGame;
use function getGameElapsedSeconds;
use function getGameSpeed;
use function is_null;

class Formulas
{
    const PROTECTION_INCREASE = 900;
    const PROTECTION_INCREASE_INTERVAL = 3600;
    public static $data;
    private static $_self;

    public static function getInstance()
    {
        if (!(self::$_self instanceof self)) {
            self::$_self = new self();
        }
        return self::$_self;
    }

    public static function load()
    {
        $baseConfigPath = defined('ROOT_PATH') ? dirname(ROOT_PATH) : dirname(dirname(dirname(__DIR__)));
        $configDir = $baseConfigPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        if (!is_file($configDir . 'buildings.php') || !is_file($configDir . 'units.php')) {
            throw new \RuntimeException('Missing game configuration files.');
        }
        $buildingsConfig = require $configDir . 'buildings.php';
        $unitsConfig = require $configDir . 'units.php';
        self::$data = [
            "oases" => [
                2 => [1 => 1],
                3 => [1 => 1, 4 => 1],
                4 => [1 => 2],
                6 => [2 => 1],
                7 => [2 => 1, 4 => 1],
                8 => [2 => 2],
                10 => [3 => 1],
                11 => [3 => 1, 4 => 1],
                12 => [3 => 2],
                14 => [4 => 1],
                15 => [4 => 2],
            ],
            'buildings' => $buildingsConfig,
            'units' => $unitsConfig,
        ];
        self::$data['buildings'][12]['cost'] = [180, 250, 500, 160];
        self::$data['buildings'][19]['breq'] = ['13' => 3, '22' => 5];
        self::$data['buildings'][22]['time'] = [2175, 1.16, 1875];
        self::$data['buildings'][33]['breq'] = ['15' => 5];
        self::$data['buildings'][35]['cost'] = [80, 120, 70, 90];
    }

    public static function getOasisEffect($type)
    {
        if (!isset(self::$data['oases'][$type])) {
            return [];
        }
        return self::$data['oases'][$type];
    }

    public static function getOasisProduction($type)
    {
        $production = array_fill(0, 4, getGameSpeed() * 10);
        foreach (self::getOasisEffect($type) as $k => $v) {
            if (!$v) {
                continue;
            }
            $production[$k - 1] *= 4 * $v;
        }
        return $production;
    }

    public static function minVacationDays()
    {
        return max(round(3 / getGameSpeed()), 1);
    }

    public static function maxVacationDays()
    {
        return max(round(Config::getProperty("game", "vacationDays") / getGameSpeed()), 3);
    }

    public static function getAngleByXY($x, $y)
    {
        $angle = rad2deg(atan2($y, $x));
        if ($angle < 0) {
            $angle += 360;
        }
        return $angle;
    }

    public static function getTrapRepairTime()
    {
        $ub = getGameSpeed();
        if ($ub <= 10) {
            $ub = 1;
        }
        $time = 60;
        $time /= $ub;
        $time *= Config::getProperty("game", "useNanoseconds") ? 1e9 : (Config::getProperty("game",
            "useMilSeconds") ? 1e3 : 1);
        if (getGameSpeed() <= 10) {
            return round($time);
        }
        return floor($time);
    }

    public static function uTrainingTime($u, $blevel, $hdp = 0, array $h = [
        0,
        0
    ], $art_eff = 1, $decrease_percent = 0, $alliance_effect = 1)
    {
        $ub = getGameSpeed();
        $ub *= getGame('extra_training_time_multiplier');
        if ($u == 99) {
            $time = 600;
            $race = 2;
        } else {
            $race = unitIdToTribe($u) - 1;
            $u = unitIdToNr($u) - 1;
            $time = self::$data['units'][$race][$u]['time'];
        }
        if ($u < 6) {
            $raceCavalryIndex = [3, 4, 2, -1, 4, 3, 3];
            $ci = $raceCavalryIndex[$race];
            $time /= $ub;
            if ($u < $ci) { // trained in barracks
                $time *= (1 - $h[0] / 100);//hero
            } else { // trained in stables
                $time *= (1 - $h[1] / 100);
            }
        } else {
            $time /= $ub;
        }
        if ($race == 0 && 3 <= $u && $u <= 5) {
            $time *= 1 - 0.01 * $hdp;
        }
        $rate = Config::getProperty("game", "useNanoseconds") ? 1e9 : (Config::getProperty("game", "useMilSeconds") ? 1e3 : 1);
        $time = ($time * pow(0.9, $blevel - 1) * $rate);
        $time *= $art_eff;
        if ($decrease_percent > 0) {
            $time *= (100 - $decrease_percent) / 100;
        }
        $time *= $alliance_effect;
        //it should be round instead of floor
        if (getGameSpeed() <= 10) {
            $time = round($time);
        }
        $time = floor($time);
        if ($time <= 0) {
            $time = 1;
        }
        return $time;
    }

    public static function getSpyId($tribe)
    {
        if ($tribe == 3 || $tribe == 7) {
            return 3;
            //this.data['units'][3];
        } else {
            return 4;
            //this.data['units'][4];
        }
    }

    public static function uSpeed($u)
    {
        $race = unitIdToTribe($u) - 1;
        $u = unitIdToNr($u) - 1;
        return self::$data['units'][$race][$u]['speed'] * Config::getProperty("game", "movement_speed_increase");
    }

    public static function merchantSpeed($race)
    {
        $speeds = [1 => 16, 12, 24, 5 => 16, 6 => 16, 7 => 20];
        $speed = $speeds[$race];
        return $speed * getGameSpeed();
    }

    public static function merchantCAP($race, $bid18, $alliance_bonus = 1)
    {
        $capacity = [1 => 500, 2 => 1000, 3 => 750, 5 => 500, 6 => 750, 7 => 500];
        return round($capacity[$race] * getGameSpeed() * ((100 + ($bid18 * 10)) / 100) * $alliance_bonus);
    }

    public static function uCarry($u)
    {
        $race = unitIdToTribe($u) - 1;
        $u = unitIdToNr($u) - 1;
        return self::$data['units'][$race][$u]['cap'];
    }

    public static function getTrapperValueByLevel($lvl)
    {
        return ($lvl > 10 ? ($lvl * $lvl + 19 * $lvl + 20) / 2 : ($lvl * $lvl + 21 * $lvl - 2) / 2) * Config::getProperty("game",
                "trap_multiplier");
    }

    public static function checkHDPEffect($u, $hdp)
    {
        return (self::uUpkeep($u) - self::uUpkeep($u, $hdp)) > 0;
    }

    public static function uUpkeep($u, $hdp = 0, $isBattle = FALSE)
    {
        if ($u == 99) {
            return 0;
        }
        if ($u == 98) {
            return 6;
        }
        $race = unitIdToTribe($u) - 1;
        if ($race === 3 && !$isBattle) {
            return 0;
        } //nature units doesn't consume
        $u = unitIdToNr($u) - 1;
        $cu = self::$data['units'][$race][$u]['cu'];
        if ($race == 0 && 3 <= $u && $u <= 5) {
            if ($u == 3 && $hdp >= 10) {
                --$cu;
            }
            if ($u == 4 && $hdp >= 15) {
                --$cu;
            }
            if ($u == 5 && $hdp >= 20) {
                --$cu;
            }
        }
        return $cu;
    }

    public static function getHDPAndNonHDPDiffCrop($u, $hdp)
    {
        return self::uUpkeep($u) - self::uUpkeep($u, $hdp);
    }

    public static function getProtectionExtendTime($registrationTime)
    {
        return round(self::getTotalProtectionTime($registrationTime) * 4 / 8);
    }

    public static function getTotalProtectionTime($registrationTime)
    {
        $base = Config::getProperty("game", "protection_time");
        $startTime = Config::getProperty("game", "start_time");
        if ($registrationTime > $startTime) {
            $difTime = $registrationTime - $startTime;
            $base += floor($difTime / self::PROTECTION_INCREASE_INTERVAL) * self::PROTECTION_INCREASE;
        }
        return $base;
    }

    public static function getProtectionShowExtendTime($registrationTime)
    {
        return round(self::getTotalProtectionTime($registrationTime) * 1 / 8);
    }

    public static function getProtectionBasicTime($registrationTime)
    {
        return round(self::getTotalProtectionTime($registrationTime) * 4 / 8);
    }

    public static function uResearchCost($u)
    {
        $coeffs = self::uResearchCostCoeffs($u);
        $cost = self::uTrainingCost($u);
        for ($r = 0; $r < 4; $r++) {
            $cost[$r] = $cost[$r] * $coeffs['k'][$r] + $coeffs['b'][$r];
        }

        return $cost;
    }

    private static function uResearchCostCoeffs($u)
    {
        $u = unitIdToNr($u) - 1;
        $stdCost = ["b" => [100, 100, 200, 160], "k" => [6, 4, 8, 6], "t" => 3];
        $adminCost = [
            "b" => [500, 200, 400, 160],
            "k" => [0.5, 0.5, 0.8, 0.6],
            "t" => 0.25,
        ];
        $nullCost = ["k" => 0, "b" => 0, "t" => 0];
        if ($u < 8) {
            return $stdCost;
        } else if ($u == 8) {
            return $adminCost;
        }

        return $nullCost;
    }

    public static function uTrainingCost($u, $big = FALSE)
    {
        if ($u == 99) {
            return [35, 30, 10, 20];
        } //trap
        $race = unitIdToTribe($u);
        if ($race == 5) $race = 1; // Natars to roman
        $race = $race - 1;
        $u = unitIdToNr($u) - 1;
        $cost = self::$data['units'][$race][$u]['cost'];
        $newSettlersCost = [
            0 => [4600, 4200, 5800, 4400],
            1 => [5800, 4400, 4600, 5200],
            2 => [4400, 5600, 4200, 3900],
            4 => [4600, 4200, 5800, 4400],
            5 => [4560, 5890, 5370, 4180],
            6 => [6100, 4600, 4800, 5400],
        ];
        if (in_array($race, [0, 1, 2, 4, 5, 6]) && ($u == 9)) {
            $nc = $newSettlersCost[$race];
            $cost[0] = $nc[0];
            $cost[1] = $nc[1];
            $cost[2] = $nc[2];
            $cost[3] = $nc[3];
        }
        if ($big) {
            foreach ($cost as &$res) {
                $res *= 3;
            }
        }
        return $cost;
    }

    public static function uResearchTime($unitId)
    {
        $rate = 1 / (getGame("useMilSeconds") ? 1000 : (getGame("useNanoseconds") ? 1e9 : 1));
        $u = unitIdToNr($unitId) - 1;
        if ($u < 8) { //normal troops
            return $rate * round5(1800 + 3 * self::uTrainingTime($unitId, 1));
        }
        // senators
        return $rate * round5(7200 + self::uTrainingTime($unitId, 1) / 4);
    }

    public static function uUpgradeCost($unitId, $lvl)
    {
        $c = pow($lvl, 0.8);
        $cost = self::uTrainingCost($unitId);
        $coeffs = self::uResearchCostCoeffs($unitId);
        for ($r = 0; $r < 4; $r++) {
            $k = ($cost[$r] * 7 + $coeffs['b'][$r]) / self::uUpkeep($unitId);

            $cost[$r] = round5($k * $c);
        }

        return $cost;
    }

    public static function uUpgradeTime($u, $lvl, $b_level)
    {
        $researchTime = self::uResearchTime($u);
        $c = pow($lvl, 0.8);
        $tc = pow(0.964, $b_level - 1);
        return round($researchTime * $c * $tc);
    }

    public static function uResearchPreRequests($race, $nr)
    {
        if (!isset(self::$data['units'][$race - 1][$nr - 1]['breq'])) {
            self::$data['units'][$race - 1][$nr - 1]['breq'] = [];
        }
        return self::$data['units'][$race - 1][$nr - 1]['breq'];
    }

    public static function fieldProduction($lvl)
    {
        $p = [
            2,
            5,
            9,
            15,
            22,
            33,
            50,
            70,
            100,
            145,
            200,
            280,
            375,
            495,
            635,
            800,
            1000,
            1300,
            1600,
            2000, /* 20 - 25 */
            2450,
            3050,
            3750,
            4600,
            5650,
            6950,
        ];
        for ($i = 25 + 1; $i <= $lvl; ++$i) {
            $p[$i] = round5($p[$i - 1] * 1.23);
        }
        return ceil($p[$lvl] * 1.4 * getGameSpeed());
    }

    public static function bigStoreCAP($lvl)
    {
        return $lvl != 0 ? self::storeCAP($lvl) * 3 : 800 * Config::getProperty("game", "storage_multiplier");
    }

    public static function storeCAP($lvl)
    {
        $lvl = max(0, min(20, $lvl));
        return round(21.2 * pow(1.2, $lvl) - 13.2) * 100 * Config::getProperty("game", "storage_multiplier");
    }

    public static function crannyCAP($lvl, $race)
    {
        $raceEffect = $race == 3 ? 3 / 2 : 1;
        $raceEffect *= Config::getProperty("game", "cranny_multiplier");
        return $lvl <= 0 ? 0 : 10 * round(10 * pow(1.2917, $lvl - 1)) * $raceEffect;
    }

    public static function trapperValue($lvl)
    {
        return (($lvl > 10 ? ($lvl * $lvl + 19 * $lvl + 20) / 2 : ($lvl * $lvl + 21 * $lvl - 2) / 2)) * Config::getProperty("game",
                "trap_multiplier");
    }

    public static function getMainBuildingValue($lvl)
    {
        return round(100 / (pow(0.964, 1 - $lvl)));
    }

    public static function celebrationCost($big = FALSE)
    {
        $times = !getCustom('batchCelebration') ? 1 : ceil(getGameSpeed() / 50);
        $res = [6400, 6650, 5940, 1340];;
        if ($big) {
            $res = [29700, 33250, 32000, 6700];
        }
        foreach ($res as &$r) {
            $r *= $times;
        }
        return $res;
    }

    public static function celebrationTime($big = FALSE, $lvl = 1)
    {
        $times = !getCustom('batchCelebration') ? 1 : ceil(getGameSpeed() / 50);
        $cool_down_time = round((($big ? 216000 : 86400) * self::townHallCelebrationTime($lvl)) / (floor(getGameSpeed() / 3) + 1));
        return $times * max($cool_down_time, 30);
    }

    public static function townHallCelebrationTime($lvl)
    {
        return pow(0.964, $lvl - 1);
    }

    public static function getCelebrationMaxCP($big = false, $curCP = null)
    {
        $times = !getCustom('batchCelebration') ? 1 : ceil(getGameSpeed() / 50);
        $values = [
            'small' => [1 => 500, 250, 250, 0, 250, 10 => 250, 20 => 125],
            'big' => [1 => 2000, 2000, 1000, 0, 1000, 10 => 1000, 20 => 500],
        ];
        if (getGameSpeed() <= 10) {
            $cp = ceil($times * $values[$big ? 'big' : 'small'][getGameSpeed()]);
        } else {
            $cp = ceil($times * $values[$big ? 'big' : 'small'][3]);
        }
        if (!is_null($curCP)) {
            return min($times * $curCP, $cp);
        }
        return $cp;
    }

    public static function getDistance($a, $b)
    {
        if (!is_array($a)) {
            $a = self::kid2xy($a);
        }
        if (!is_array($b)) {
            $b = self::kid2xy($b);
        }
        return hypot(self::delta($b['x'], $a['x']), self::delta($b['y'], $a['y']));
    }

    public static function kid2xy($kid)
    {
        $max = 2 * MAP_SIZE + 1;
        $x = ($kid % $max) ? ($kid % $max) - (MAP_SIZE + 1) : MAP_SIZE;
        $y = MAP_SIZE - ($kid - (MAP_SIZE + 1) - $x) / $max;
        return ["x" => $x, "y" => $y];
    }

    public static function delta($a, $b)
    {
        return ($a - $b + ((2 * MAP_SIZE + 1 + MAP_SIZE))) % (2 * MAP_SIZE + 1) - MAP_SIZE;
    }

    public static function xy2kid($x, $y)
    {
        return (1 + self::coordinateFixer((int)$x) + MAP_SIZE) + ((2 * MAP_SIZE + 1) * abs(self::coordinateFixer((int)$y) - MAP_SIZE));
    }

    public static function coordinateFixer($x)
    {
        $x = (int)$x;
        $max = 2 * MAP_SIZE + 1;
        if ($x < -MAP_SIZE) {
            $x += $max;
        }
        if ($x > MAP_SIZE) {
            $x -= $max;
        }
        return $x;
    }

    public static function coordinateFixer2($k)
    {
        if (($k < -MAP_SIZE) || ($k > MAP_SIZE)) {
            $k = abs(($k + MAP_SIZE) % (2 * MAP_SIZE + 1)) - MAP_SIZE;
        }
        return $k;
    }

    public static function isGrayArea($kid)
    {
        $xy = is_array($kid) ? $kid : self::kid2xy($kid);

        return round(hypot($xy['x'], $xy['y'])) <= 22;
    }

    public static function getMapMinDistanceFromCenter($elapsed = null, $rate = 1)
    {
        if (IS_DEV) return 0;
        if (is_null($elapsed)) {
            $useMiliseconds = true;
            if ($useMiliseconds) {
                $elapsed = getGameElapsedMiliSeconds();
                $rate = 1000;
            } else {
                $rate = 1;
                $elapsed = getGameElapsedSeconds();
            }
        }
        $elapsed = max(0, $elapsed);
        $round_length = getGame('round_length_real');
        if (getGameSpeed() <= 10) {
            $max_distance = floor(hypot(MAP_SIZE, MAP_SIZE)) * 0.663;
        } else {
            $max_distance = floor(hypot(MAP_SIZE, MAP_SIZE)) * 0.5;
        }
        $distance_increase_per_second = $max_distance / $round_length / 86400 / $rate;
        if ($elapsed <= (10800 * $rate)) {
            $distance_increase_per_second *= 25;
        }
        return 25 + ($elapsed * $distance_increase_per_second);
    }

    public static function heroSpeed($race, $isCavalry = false)
    {
        //TODO: hero speed for huns and egyptions
        $rate = getGame("movement_speed_increase");
        if ($race == 3) {
            return (7 + ($isCavalry ? 5 : 0)) * $rate;
        }
        return 7 * $rate;
    }

    public static function getEmbassyMembersCount($lvl)
    {
        return $lvl < 3 ? 0 : $lvl * 3;
    }

    public static function getWallID($race)
    {
        return [1 => 31, 32, 33, 0, 31, 42, 43][$race];
    }

    public static function wallPower($race, $lvl)
    {
        $k = [1 => 1.03, 2 => 1.02, 3 => 1.025, 4 => 1, 5 => 1.03, 6 => 1.025, 7 => 1.015][$race];
        return round((100 * (pow($k, $lvl) - 1)));
    }

    public static function TradeOfficeValue($lvl)
    {
        return (10 + $lvl) * 10;
    }

    public static function TournamentSqValue($lvl)
    {
        return (10 + $lvl) * 10;
    }

    public static function StonemasonsLodgeValue($lvl)
    {
        return (10 + $lvl) * 10;
    }


    public static function buildingUpgradeTime($gid, $lvl, $mb, $n = FALSE)
    {
        $rate = getGameSpeed();
        $t = array_values(self::$data['buildings'][$gid - 1]['time']);

        if (sizeof($t) < 3) {
            $t[1] = 1.16;
            if (sizeof($t) === 1) {
                $t[1] = 1;
            }
            $t[2] = 1875 * $t[1];
        }

        $time = (($t[0] * pow($t[1], $lvl - 1) - $t[2]) * ($mb != 0 ? pow(0.964,
                $mb - 1) : 5) / $rate / ($n ? 2 : 1));

        if ($rate > 500) {
            if ($gid == 40) {
                if ($lvl < 50 && $time < 30) {
                    return 30;
                } else if ($lvl < 90 && $time < 90) {
                    return 60;
                } else if ($time < 120) {
                    return 120;
                }
            }
        }

        return $rate < 10 ? round10($time) : round($time);
    }

    public static function buildingUpgradeCosts($gid, $lvl)
    {
        if (!$gid) {
            return [0, 0, 0, 0];
        }
        if ($gid == 99) {
            $gid = 40;
        }//w :>
        $cost = self::$data['buildings'][$gid - 1]['cost'];
        $k = self::$data['buildings'][$gid - 1]['k'];
        for ($r = 0; $r < 4; ++$r) {
            $cost[$r] = round($cost[$r] * pow($k, $lvl - 1) / 5) * 5;
            if ($gid == 40) {
                if ((($lvl == 100) && ($r < 3)) || ($cost[$r] > 1e6)) {
                    $cost[$r] = 1e6;
                }
            }
        }
        if ($gid <= 4 && $lvl > 20 && getGameSpeed() > 10) {
            $rate = 10;
            foreach ($cost as &$r) {
                $r *= $rate;
            }
        }
        return $cost;
    }

    public static function buildingCpPop($item_id, $lvl, $to_lvl, $noCP = FALSE)
    {
        $pop = 0;
        $tmp1 = min($lvl, $to_lvl);
        $tmp2 = max($lvl, $to_lvl);
        $diff = $tmp2 - $tmp1;
        for ($i = 1; $i <= $diff; ++$i) {
            $pop += self::buildingCropConsumption($item_id, $tmp1 + $i);
        }
        $cp = abs(-self::buildingCP($item_id, $lvl) + self::buildingCP($item_id, $to_lvl));
        return [$pop, $noCP ? 0 : $cp];
    }

    public static function buildingCropConsumption($gid, $lvl, $n = false)
    {
        if ($lvl <= 0) {
            return 0;
        }
        if (!isset(self::$data['buildings'][$gid - 1]['cu'])) {
            return 0;
        }
        $cu = self::$data['buildings'][$gid - 1]['cu'];

        //if ($n && C("Game.version") < 4) $cu /= 2;
        return ($lvl <> 1) ? round((5 * $cu + ($lvl - 1)) / 10) : $cu;
    }

    public static function buildingCP($gid, $lvl)
    {
        if ($lvl <= 0) {
            return 0;
        }
        if (!isset(self::$data['buildings'][$gid - 1])) {
            return 0;
        }

        return round(self::$data['buildings'][$gid - 1]['cp'] * pow(1.2, $lvl));
    }

    public static function buildingMaxLvl($gid, $capital, $real = true)
    {
        if ($gid == 40) return 100;
        if ($gid <= 4) {
            if (!$capital) {
                return 10;
            }
            return !$real ? 20 : (getCustom('allowResourcesToGoToMaximumPossible') ? 1e9 : 21);
        }
        $maxLvl = (int)self::$data['buildings'][$gid - 1]['maxLvl'];
        if (isset(self::$data['buildings'][$gid - 1]['req']) && isset(self::$data['buildings'][$gid - 1]['capital']) && (int)self::$data['buildings'][$gid - 1]['capital'] > 1) {
            $maxLvl = !$capital ? (int)self::$data['buildings'][$gid - 1]['capital'] : $maxLvl;
        }
        return $maxLvl;
    }

    public static function countCPVillages($cp)
    {
        $multiplier = ceil(getGame('movement_speed_increase') / 50);
        $speed = min(getGameSpeed(), 10);
        $n = round(pow($cp / 1600 * $speed / $multiplier, 1 / 2.3) + 1, 1, -2);
        $n = floor($n);
        if ($cp < self::newVillageCP($n)) {
            $n--;
        }
        return $n;
    }

    public static function newVillageCP($n)
    {
        $multiplier = ceil(getGame('movement_speed_increase') / 50);
        $speed = min(getGameSpeed(), 10);
        return $multiplier * round(1600 * pow($n - 1, 2.3) / $speed, $speed >= 3 ? -2 : -3);
    }

    public static function heroLevel($exp)
    {
        $x = self::getHeroExpLevelMultiplier();
        $exp = ceil($exp / $x);
        return floor((sqrt(4 * $exp + 25) - 5) / 10);
    }

    public static function getHeroExpLevelMultiplier()
    {
        $x = 1;
        //fix of levels
        if (getGameSpeed() <= 10) {
            $x = 1;
        } else if (getGameSpeed() <= 1000) {
            $x = 20;
        } else if (getGameSpeed() <= 2000) {
            $x = 40;
        } else if (getGameSpeed() <= 3000) {
            $x = 60;
        } else if (getGameSpeed() <= 5000) {
            $x = 100;
        } else if (getGameSpeed() <= 10000) {
            $x = 300;
        } else if (getGameSpeed() <= 20000) {
            $x = 600;
        }
        return $x;
    }

    public static function heroExperience($level)
    {
        $x = self::getHeroExpLevelMultiplier();
        return $x * 25 * $level * ($level + 1);
    }

    public static function heroRegenerationTime($level)
    {
        $rate = Config::getProperty("game", "useNanoseconds") ? 1e9 : (Config::getProperty("game",
            "useMilSeconds") ? 1e3 : 1);
        return round(min($level + 1, 24) / floor(getGameSpeed() / 3 + 1) * 3600 * $rate);
    }

    public static function heroRegenerateCost($lvl, $tribe_id)
    {
        $tribe_costs = [
            1 => [130, 115, 180, 75],
            2 => [180, 130, 115, 75],
            3 => [115, 180, 130, 75],
            5 => [130, 115, 180, 75],
            6 => [115, 180, 130, 75],
            7 => [125, 125, 125, 125],
            //natars are same as romans.
        ];
        $cost = [];
        $lvl = min($lvl, 100);
        for ($r = 0; $r < 4; $r++) {
            $cost[$r] = self::trickyRounding($tribe_costs[$tribe_id][$r] * (1 + $lvl / 24) * (1 + $lvl), $lvl);
        }
        return $cost;
    }

    private static function trickyRounding($value, $lvl)
    {
        if ($lvl < 5) {
            $round_size = 10;
        } else if ($lvl < 10) {
            $round_size = 50;
        } else {
            $round_size = 100;
        }

        return round($value / $round_size) * $round_size;
    }

    public static function getFestivalDuration()
    {
        $duration = 72 * 3600; //it's 3 days.
        if (getGameSpeed() > 20) {
            if (getGameSpeed() <= 100) {
                $duration /= getGameSpeed();
            } else {
                $duration /= getGameSpeed() / 2;
            }
        }
        return max(ceil($duration), 300);
    }

    public static function getFestivalResources()
    {
        $res = [3870, 1680, 5940, 1340];
        if (getGameSpeed() > 20) {
            $res = array_map(function ($x) {
                return $x * ceil(getGameSpeed() / 20);
            },
                $res);
        }
        return $res;
    }

    public static function getOasisStorage($typeId)
    {
        $maxStore = 1000 * Config::getProperty("game", "storage_multiplier");
        if (in_array($typeId, [3, 7, 11, 15])) {
            $maxStore *= 2;
        }
        return $maxStore;
    }

    public static function getArtworkReleaseTime()
    {
        /**
         * 1x => 14 days
         */
        $rate = 14 / 365 * Config::getProperty("game", "round_length") * 86400;
        if (getGameSpeed() <= 1) {
            $rate = 14 * 86400;
        } else if (getGameSpeed() <= 2) {
            $rate = 10 * 86400;
        } else if (getGameSpeed() <= 10) {
            $rate = 7 * 86400;
        }
        return Config::getInstance()->game->start_time + $rate;
    }

    public static function getCurrentHeroItemsTier()
    {
        /**
         * Level 1: until day 75 of server
         * Level 2: days 75 - 165 of server
         * Level 3: starting on day 165 of server
         */
        $rate_tier1 = 75 / 350 * Config::getProperty("game", "round_length") * 86400;
        $rate_tier2 = 165 / 350 * Config::getProperty("game", "round_length") * 86400;

        $tear_time1 = getGameElapsedSeconds() <= $rate_tier1;
        $tear_time2 = !$tear_time1 && getGameElapsedSeconds() <= $rate_tier2;
        $tearNum = ($tear_time1 ? 1 : ($tear_time2 ? 2 : 3));
        return $tearNum;
    }

    public static function getAllianceBonusLevel($contributes)
    {
        $level = 0;
        for ($i = 1; $i <= 5; ++$i) {
            if ($contributes >= self::getAllianceBonusContributesNeededForLevel($i)) {
                $level = $i;
            }
        }
        return $level;
    }

    public static function getAllianceBonusContributesNeededForLevel($level, $previousAdded = true)
    {
        $rate = 1;
        if (getGameSpeed() > 20) {
            $rate = ceil(getGameSpeed() / 1000);
        }
        $result = [
            0 => 0,
            1 => 2400000,
            2 => 19200000,
            3 => 38400000,
            4 => 76800000,
            5 => 153600000,
        ];
        if (!$previousAdded) {
            return $rate * $result[$level];
        }
        $x = 0;
        for ($i = $level; $i >= 0; --$i) {
            $x += $result[$i];
        }
        return $x * $rate;
    }

    public static function getAllianceBonusCoolDownForNewPlayers($level)
    {
        return ceil(($level - 2) * 86400 / getGameSpeed());
    }

    public static function getAllianceBonusDonationLimit($level)
    {
        $levels = [
            0 => 300000,
            1 => 400000,
            2 => 550000,
            3 => 750000,
            4 => 1000000,
            5 => 1000000,
        ];
        return $levels[$level] * getGameSpeed();
    }

    public static function getAllianceBonusUpgradeDuration($level)
    {
        return round(($level * 86400) / getGameSpeed());
    }

    public static function getFieldTypeResourceMap($fieldtype)
    {
        $types = [
            1 => '4,4,1,4,4,2,3,4,4,3,3,4,4,1,4,2,1,2',
            2 => '3,4,1,3,2,2,3,4,4,3,3,4,4,1,4,2,1,2',
            3 => '1,4,1,3,2,2,3,4,4,3,3,4,4,1,4,2,1,2',
            4 => '1,4,1,2,2,2,3,4,4,3,3,4,4,1,4,2,1,2',
            5 => '1,4,1,3,1,2,3,4,4,3,3,4,4,1,4,2,1,2',
            6 => '4,4,1,3,4,4,4,4,4,4,4,4,4,4,4,2,4,4',
            7 => '1,4,4,1,2,2,3,4,4,3,3,4,4,1,4,2,1,2',
            8 => '3,4,4,1,2,2,3,4,4,3,3,4,4,1,4,2,1,2',
            9 => '3,4,4,1,1,2,3,4,4,3,3,4,4,1,4,2,1,2',
            10 => '3,4,1,2,2,2,3,4,4,3,3,4,4,1,4,2,1,2',
            11 => '3,1,1,3,1,4,4,3,3,2,2,3,1,4,4,2,4,4',
            12 => '1,4,1,1,2,2,3,4,4,3,3,4,4,1,4,2,1,2,1',
        ];
        return explode(",", $types[$fieldtype]);
    }

    public static function getFieldTypeResourceArr($fieldtype)
    {
        $types = [
            1 => '3-3-3-9',
            2 => '3-4-5-6',
            3 => '4-4-4-6',
            4 => '4-5-3-6',
            5 => '5-3-4-6',
            6 => '1-1-1-15',
            7 => '4-4-3-7',
            8 => '3-4-4-7',
            9 => '4-3-4-7',
            10 => '3-5-4-6',
            11 => '4-3-5-6',
            12 => '5-4-3-6',
        ];
        return explode("-", $types[$fieldtype]);
    }

    public static function getNearbyXY($x, $y, $max_distance)
    {
        $angle = atan2($y, $x);
        $r = hypot($x, $y);
        $half = $max_distance / 2;
        $pointRadius = mt_rand($r - $half, $r + $half);
        $point = [
            'x' => ceil(cos($angle) * $pointRadius),
            'y' => ceil(sin($angle) * $pointRadius),
        ];
        return $point;
    }

    public static function getXYAngle($x, $y)
    {
        $angle = rad2deg(atan2($y, $x));
        if ($angle < 0) {
            $angle += 360;
        }
        return $angle;
    }

}

Formulas::load();