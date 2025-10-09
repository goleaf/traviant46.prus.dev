<?php

namespace Auth\Verification;

use Core\Database\DB;
use Core\Database\GlobalDB;

class EmailVerificationService
{
    public function beginVerification(int $uid, string $email, string $token): void
    {
        $db = DB::getInstance();
        $email = $db->real_escape_string($email);
        $token = $db->real_escape_string($token);
        $db->query("INSERT INTO activation_progress (uid, email, token, time) VALUES ($uid, '$email', '$token', " . time() . ") ON DUPLICATE KEY UPDATE email=VALUES(email), token=VALUES(token), time=VALUES(time)");
    }

    public function markVerified(int $uid, string $email): void
    {
        $db = DB::getInstance();
        $email = $db->real_escape_string($email);
        $db->query("UPDATE users SET email='$email', email_verified=1 WHERE id=$uid");
    }

    public function tokenMatches(string $token): ?array
    {
        $db = DB::getInstance();
        $token = $db->real_escape_string($token);
        $result = $db->query("SELECT * FROM activation_progress WHERE token='$token' LIMIT 1");
        if (!$result->num_rows) {
            return null;
        }
        return $result->fetch_assoc();
    }

    public function clearToken(int $id): void
    {
        $db = DB::getInstance();
        $db->query("DELETE FROM activation_progress WHERE id=$id");
    }

    public function activationTokenExists(string $token): bool
    {
        $global = GlobalDB::getInstance();
        $token = $global->real_escape_string($token);
        $result = $global->query("SELECT COUNT(id) FROM activation WHERE token='$token'");
        return (int)$result->fetch_row()[0] > 0;
    }
}
