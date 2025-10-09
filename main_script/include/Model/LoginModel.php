<?php
namespace Model;

use Auth\Services\PasswordResetService;
use Auth\Services\SitterService;
use Auth\Services\UserLookupService;

class LoginModel
{
    private $userLookup;
    private $passwordResetService;
    private $sitterService;

    public function __construct()
    {
        $this->userLookup = new UserLookupService();
        $this->passwordResetService = new PasswordResetService();
        $this->sitterService = new SitterService();
    }

    public function findLogin($name)
    {
        return $this->userLookup->findByIdentifier($name);
    }

    public function addNewPassword($row)
    {
        $this->passwordResetService->queuePasswordReset($row);
    }

    public function checkUserOrSitterLogin($uid, $password)
    {
        return $this->sitterService->checkUserOrSitterLogin((int)$uid, $password);
    }

    public function checkLogin($password, $result)
    {
        return $this->sitterService->checkLogin($password, $result);
    }
}
