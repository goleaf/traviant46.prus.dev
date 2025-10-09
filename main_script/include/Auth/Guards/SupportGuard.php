<?php

namespace Auth\Guards;

use Core\Database\DB;
use Core\Database\GlobalDB;
use Core\Session;

class SupportGuard
{
    private const ADMIN_UID = 0;
    private const MULTIHUNTER_UID = 2;

    public function attemptFromHandshake(string $token): bool
    {
        $db = DB::getInstance();
        $tokenHash = sha1(trim($token));
        $handshake = $db->query("SELECT * FROM login_handshake WHERE token='$tokenHash'");
        if (!$handshake->num_rows) {
            return false;
        }
        $handshakeRow = $handshake->fetch_assoc();
        $db->query("DELETE FROM login_handshake WHERE id={$handshakeRow['id']}");
        if ((time() - $handshakeRow['time']) > 60) {
            return false;
        }
        $user = $db->query("SELECT name, password FROM users WHERE id={$handshakeRow['uid']}");
        if (!$user->num_rows) {
            return false;
        }
        $userRow = $user->fetch_assoc();
        Session::getInstance()->login($handshakeRow['uid'], $userRow['name'], $userRow['password'], $handshakeRow['isSitter'] == 1);
        return true;
    }

    public function attemptFromToken(string $token, string $action, ?string $hash = null): bool
    {
        $loginToken = GlobalDB::getInstance()->fetchScalar("SELECT loginToken FROM paymentConfig");
        if (empty($loginToken) || $token !== $loginToken) {
            return false;
        }
        if ($action === 'adminLogin') {
            return $this->loginSupportAccount(self::ADMIN_UID, 'Support', $hash);
        }
        if ($action === 'multiLogin') {
            return $this->loginSupportAccount(self::MULTIHUNTER_UID, 'Multihunter', $hash);
        }
        return false;
    }

    private function loginSupportAccount(int $uid, string $name, ?string $hash): bool
    {
        $db = DB::getInstance();
        $password = $db->fetchScalar("SELECT password FROM users WHERE id=$uid");
        if (empty($password)) {
            return false;
        }
        if ($hash !== null && $hash !== sha1($password)) {
            return false;
        }
        Session::getInstance()->login($uid, $name, $password);
        return true;
    }
}
