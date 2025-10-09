<?php

namespace Auth\Services;

use Core\Config;
use Core\Database\DB;
use Core\Database\GlobalDB;

class UserLookupService
{
    public function findByIdentifier(string $identifier): array
    {
        $db = DB::getInstance();
        $name = $db->real_escape_string(htmlspecialchars($identifier, ENT_QUOTES));
        $loginType = 0;
        $userRow = [];
        do {
            $find = $db->query("SELECT id, name, email, sit1Uid, sit2Uid, password, last_owner_login_time FROM users WHERE (name='$name' OR email='$name') LIMIT 1");
            if ($find->num_rows) {
                $loginType = 1;
                $userRow = $find->fetch_assoc();
                $find->free();
                break;
            }
            $find->free();
            $find = $db->query("SELECT id, token, password FROM activation WHERE (name='$name' OR email='$name') LIMIT 1");
            if ($find->num_rows) {
                $loginType = 2;
                $userRow = $find->fetch_assoc();
                $find->free();
                break;
            }
            $find->free();
            $activation = $this->getActivation(Config::getProperty('settings', 'worldUniqueId'), $name);
            if ($activation !== false) {
                $loginType = 3;
                $userRow = $activation;
                $userRow['password'] = sha1($userRow['password']);
                break;
            }
        } while (false);
        return ['type' => $loginType, 'row' => $userRow];
    }

    private function getActivation($worldId, $name)
    {
        $globalDB = GlobalDB::getInstance();
        $worldId = $globalDB->real_escape_string($worldId);
        $name = $globalDB->real_escape_string($name);
        $find = $globalDB->query("SELECT id, name, password FROM activation WHERE worldId='$worldId' AND used=0 AND (name='$name' OR email='$name')");
        if ($find->num_rows) {
            return $find->fetch_assoc();
        }
        return false;
    }
}
