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

        /**
         * Guard the namespace registration to support Livewire 3 where
         * componentNamespace() was removed in favour of Volt auto-discovery.
         */
        if (method_exists(Livewire::class, 'componentNamespace')) {
            Livewire::componentNamespace('App\\Livewire\\Game', 'game');
        }
    }
}
