<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * Service provider responsible for configuring Livewire defaults and shared namespaces.
 */
final class LivewireServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Config::set('livewire.class_namespace', 'App\\Livewire');
        Config::set('livewire.view_path', resource_path('views/livewire'));
    }

    public function boot(): void
    {
        View::addNamespace('game', resource_path('views/livewire/game'));

        Livewire::resolveMissingComponent(function (string $name): ?string {
            if (! Str::startsWith($name, 'game::')) {
                return null;
            }

            $component = Str::of($name)
                ->after('game::')
                ->explode('.')
                ->map(fn (string $segment): string => Str::studly($segment))
                ->implode('\\');

            if ($component === '') {
                return null;
            }

            $class = 'App\\Livewire\\Game\\'.$component;

            return class_exists($class) ? $class : null;
        });
    }
}
