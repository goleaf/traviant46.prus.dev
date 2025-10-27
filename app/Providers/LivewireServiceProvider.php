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
    /**
     * Register the base Livewire configuration so components resolve consistently.
     */
    public function register(): void
    {
        Config::set('livewire.class_namespace', 'App\\Livewire');
        Config::set('livewire.view_path', resource_path('views/livewire'));
    }

    /**
     * Bootstrap Livewire by wiring the game namespace and shared view folder.
     */
    public function boot(): void
    {
        View::addNamespace('game', resource_path('views/livewire/game'));

        Livewire::resolveMissingComponent(
            /**
             * Map `game.*` component aliases onto the Livewire game namespace while
             * remaining compatible with Livewire v3 where componentNamespace was removed.
             */
            static function (string $alias): ?string {
                if (! str_starts_with($alias, 'game.')) {
                    return null;
                }

                $segments = explode('.', Str::after($alias, 'game.'));
                $class = 'App\\Livewire\\Game\\'.collect($segments)
                    ->map(static fn (string $segment): string => Str::studly($segment))
                    ->implode('\\');

                return class_exists($class) ? $class : null;
            },
        );
    }
}
