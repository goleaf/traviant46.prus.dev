<?php

namespace Auth\Services;

use Core\Database\DB;

class SitterService
{
    public function checkUserOrSitterLogin(int $uid, string $password): int
    {
        $user = $this->findUserLoginById($uid);
        if (!$user->num_rows) {
            $user->free();
            return 1;
        }
        $row = $user->fetch_assoc();
        $user->free();
        if ($row['password'] === $password) {
            return 0;
        }
        if ($row['sit1Uid'] && $this->getSitterPassword((int)$row['sit1Uid']) === $password) {
            return 2;
        }
        if ($row['sit2Uid'] && $this->getSitterPassword((int)$row['sit2Uid']) === $password) {
            return 3;
        }
        return 1;
    }

    public function checkLogin(string $password, array $result): int
    {
        if ($result['row']['password'] === $password) {
            return 0;
        }
        if ($result['type'] == 1) {
            if ($result['row']['sit1Uid'] && $this->getSitterPassword((int)$result['row']['sit1Uid']) === $password) {
                return 1;
            }
            if ($result['row']['sit2Uid'] && $this->getSitterPassword((int)$result['row']['sit2Uid']) === $password) {
                return 2;
            }
        }
        return 3;
    }

    private function findUserLoginById(int $uid)
    {
        $db = DB::getInstance();
        return $db->query("SELECT id, sit1Uid, sit2Uid, password FROM users WHERE id=$uid LIMIT 1");
    }

    private function getSitterPassword(int $uid)
    {
        if (!$uid) {
            return false;
        }
        $db = DB::getInstance();
        $row = $db->query("SELECT password FROM users WHERE id=$uid LIMIT 1");
        if ($row->num_rows) {
            $result = $row->fetch_assoc();
            $row->free();
            return $result['password'];
        }
        $row->free();
        return false;
    }
}
