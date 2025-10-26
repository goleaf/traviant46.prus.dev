<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Livewire\Mechanisms\ComponentRegistry;

it('configures Livewire defaults and registers the game view namespace', function (): void {
    expect(Config::get('livewire.class_namespace'))->toBe('App\\Livewire');
    expect(Config::get('livewire.view_path'))->toBe(resource_path('views/livewire'));
    expect(View::getFinder()->getHints())->toHaveKey('game');
});

it('maps game-prefixed component aliases to the game namespace', function (): void {
    if (! class_exists(\App\Livewire\Game\Diagnostics::class)) {
        eval(<<<'PHP_CLASS'
            namespace App\Livewire\Game;

            final class Diagnostics extends \Livewire\Component
            {
                public function render(): \Illuminate\Contracts\View\View
                {
                    return view('layouts.game', [
                        'slot' => 'Diagnostics report',
                    ]);
                }
            }
        PHP_CLASS);
    }

    $registry = app(ComponentRegistry::class);

    expect($registry->getClass('game::diagnostics'))->toBe(\App\Livewire\Game\Diagnostics::class);
});
