<?php

namespace Model;

use App\Services\MarketService as BaseMarketService;
use Core\Database\DB;

class MarketModel extends BaseMarketService
{
    /**
     * Creates a new marketplace offer and returns the generated identifier.
     *
     * @param int $kid        Village identifier of the offer owner.
     * @param int $aid        Alliance identifier that can accept the offer (0 = everyone).
     * @param int $hours      Maximum allowed travel time in hours (0 = no limit).
     * @param int $needType   Resource type that the owner requests (1-4).
     * @param int $needValue  Amount of the requested resource.
     * @param int $giveType   Resource type that the owner provides (1-4).
     * @param int $giveValue  Amount of the provided resource.
     *
     * @return int The identifier of the created offer or 0 on failure.
     */
    public function createOffer(
        int $kid,
        int $aid,
        int $hours,
        int $needType,
        int $needValue,
        int $giveType,
        int $giveValue
    ): int {
        $giveValue = max(0, $giveValue);
        $needValue = max(0, $needValue);
        if ($giveValue === 0) {
            return 0;
        }

        $aid = max(0, $aid);
        $hours = max(0, $hours);
        $needType = max(1, min(4, $needType));
        $giveType = max(1, min(4, $giveType));

        parent::addOffer($kid, $aid, $hours, $needType, $needValue, $giveType, $giveValue);

        $connection = DB::getInstance();
        if ($connection->affectedRows() <= 0) {
            return 0;
        }

        return (int)$connection->lastInsertId();
    }

    /**
     * Sends resources between two villages and returns the movement identifier.
     */
    public function sendResources(
        int $kid,
        int $to_kid,
        int $race,
        int $r1,
        int $r2,
        int $r3,
        int $r4,
        int $repeat,
        int $time = -1
    ): int {
        $r1 = max(0, $r1);
        $r2 = max(0, $r2);
        $r3 = max(0, $r3);
        $r4 = max(0, $r4);
        $repeat = max(1, $repeat);

        $connection = DB::getInstance();
        parent::sendResources($kid, $to_kid, $race, $r1, $r2, $r3, $r4, $repeat, $time);

        if ($connection->affectedRows() <= 0) {
            return 0;
        }

        return (int)$connection->lastInsertId();
    }

    /**
     * Calculates the amount of merchants required to transport the provided resources.
     *
     * The method accepts either an array of resources or a variadic list of
     * resource amounts followed by the merchant capacity. Optional repeat counts
     * can be supplied as the third parameter or inside the resource array using
     * the `repeat` or `times` keys.
     */
    public function calculateMerchants($resources, ?int $merchantCapacity = null, int $repeat = 1): int
    {
        if (!is_array($resources)) {
            $arguments = func_get_args();
            if (count($arguments) < 2) {
                return 0;
            }
            $merchantCapacity = (int)array_pop($arguments);
            $repeat = 1;
            $resources = $arguments;
        }

        if ($merchantCapacity === null) {
            return 0;
        }

        if (isset($resources['repeat']) && $repeat === 1) {
            $repeat = (int)$resources['repeat'];
            unset($resources['repeat']);
        }
        if (isset($resources['times']) && $repeat === 1) {
            $repeat = (int)$resources['times'];
            unset($resources['times']);
        }

        $repeat = max(1, $repeat);
        $merchantCapacity = (int)$merchantCapacity;
        if ($merchantCapacity <= 0) {
            return 0;
        }

        $allowedStringKeys = ['wood', 'clay', 'iron', 'crop'];
        $totalResources = 0;
        foreach ($resources as $key => $value) {
            if (!is_numeric($value)) {
                continue;
            }

            $include = false;
            if (is_int($key)) {
                $include = true;
            } elseif (is_string($key)) {
                if (in_array($key, $allowedStringKeys, true)) {
                    $include = true;
                } elseif (preg_match('/^r[1-4]$/', $key) === 1) {
                    $include = true;
                }
            }

            if ($include) {
                $totalResources += max(0, (int)$value);
            }
        }

        if ($totalResources <= 0) {
            return 0;
        }

        $totalResources *= $repeat;

        return (int)ceil($totalResources / $merchantCapacity);
    }
}
