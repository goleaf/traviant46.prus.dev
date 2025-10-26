<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

require_once __DIR__.'/Stubs/FakeGameReports.php';
require_once __DIR__.'/Stubs/FakeRallyPoint.php';
require_once __DIR__.'/Stubs/FakeLivewirePage.php';

if (! class_exists(\GameReports::class, false)) {
    class_alias(\Tests\Stubs\FakeGameReports::class, \GameReports::class);
}

if (! class_exists(\GameRallyPoint::class, false)) {
    class_alias(\Tests\Stubs\FakeRallyPoint::class, \GameRallyPoint::class);
}

foreach ([
    'App\\Livewire\\Account\\BannedNotice',
    'App\\Livewire\\Account\\TrustedDevices',
    'App\\Livewire\\Account\\VerificationPrompt',
    'App\\Livewire\\Admin\\Dashboard',
    'App\\Livewire\\Admin\\PlayerAudit',
    'App\\Livewire\\Game\\Messages',
    'App\\Livewire\\Game\\RallyPoint',
    'App\\Livewire\\Game\\Reports',
    'App\\Livewire\\System\\MaintenanceNotice',
    'App\\Livewire\\Village\\Infrastructure',
    'App\\Livewire\\Village\\Overview',
] as $fqcn) {
    if (! class_exists($fqcn, false)) {
        class_alias(\Tests\Stubs\FakeLivewirePage::class, $fqcn);
    }
}
