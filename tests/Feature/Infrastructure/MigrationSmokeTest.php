<?php

declare(strict_types=1);

use App\Models\User;

it('runs migrations and seeds successfully from a clean slate', function (): void {
    $this->artisan('migrate:fresh', ['--seed' => true, '--force' => true])->assertExitCode(0);

    expect(User::query()->count())->toBeGreaterThan(0);
});
