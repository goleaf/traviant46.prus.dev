<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
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

        $livewire = Livewire::getFacadeRoot();

        // Livewire v3 removed componentNamespace(), so guard the call for forward compatibility.
        if ($livewire !== null && method_exists($livewire, 'componentNamespace')) {
            $livewire->componentNamespace('App\\Livewire\\Game', 'game');
        }
    }
}
