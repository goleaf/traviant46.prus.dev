<?php

namespace App\Jobs;

use Model\FakeUserModel;

class ProcessFakeUsers
{
    private FakeUserModel $fakeUserModel;

    public function __construct(?FakeUserModel $fakeUserModel = null)
    {
        $this->fakeUserModel = $fakeUserModel ?? new FakeUserModel();
    }

    public function __invoke(): void
    {
        $this->fakeUserModel->handleFakeUsers();
        $this->fakeUserModel->handleFakeUserExpands();
    }

    public function runAction(): void
    {
        $this();
    }
}
