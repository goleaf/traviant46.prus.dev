<?php

declare(strict_types=1);

use Tests\TestCase;

if (! class_exists(\App\Livewire\Game\RallyPoint::class)) {
    class_alias(\Tests\Stubs\FakeRallyPoint::class, \App\Livewire\Game\RallyPoint::class);
}

if (! class_exists(\App\Livewire\Game\Reports::class)) {
    class_alias(\Tests\Stubs\FakeGameReports::class, \App\Livewire\Game\Reports::class);
}

if (! class_exists(\GameReports::class)) {
    class_alias(\Tests\Stubs\FakeGameReports::class, \GameReports::class);
}

uses(TestCase::class)->in('Feature', 'Unit');
