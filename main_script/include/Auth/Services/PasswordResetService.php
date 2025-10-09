<?php

namespace Auth\Services;

use Core\Config;
use Core\Database\DB;
use Core\Helper\Mailer;
use Core\Helper\WebService;

class PasswordResetService
{
    public function queuePasswordReset(array $userRow): void
    {
        $db = DB::getInstance();
        $db->query("DELETE FROM newproc WHERE uid={$userRow['id']}");
        $time = time();
        $newPassword = substr(sha1(sha1($time + mt_rand() + mt_rand())), 0, 7);
        $cpw = get_random_string(7);
        $db->query("INSERT INTO newproc (uid, cpw, npw, time) VALUES ({$userRow['id']}, '$cpw', '$newPassword', $time)");
        $link = WebService::get_base_url() . '/password.php?cpw=' . $cpw . '&npw=' . $db->lastInsertId();
        $html = vsprintf(T('Login', 'pw_forgot_email'), [
            $userRow['name'],
            $userRow['name'],
            $userRow['email'],
            $newPassword,
            Config::getInstance()->settings->worldId,
            $link,
            $link,
        ]);
        Mailer::sendEmail($userRow['email'], T('Login', 'PasswordForgotten?'), $html);
    }
}
