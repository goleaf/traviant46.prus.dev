<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\RallyPoint;
use Livewire\Livewire;
use Tests\TestCase;

class RallyPointTest extends TestCase
{
    public function test_renders_successfully()
    {
        Livewire::test(RallyPoint::class)
            ->assertStatus(200);
    }
}
