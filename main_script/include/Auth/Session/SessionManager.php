<?php

namespace Auth\Session;

use Core\Session;

class SessionManager
{
    public function login(int $uid, string $name, string $passwordHash, bool $isSitter = false): void
    {
        Session::getInstance()->login($uid, $name, $passwordHash, $isSitter);
    }

    public function logout(): bool
    {
        return Session::getInstance()->logout();
    }

    public function regenerateChecker(): void
    {
        Session::getInstance()->changeChecker();
    }

    public function getChecker(): string
    {
        return Session::getInstance()->getChecker();
    }
}
