<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\LivewireManager;

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

        /** @var LivewireManager|null $manager */
        $manager = Livewire::getFacadeRoot();

        // Preserve legacy namespace registration when the Livewire manager still exposes the helper.
        if ($manager instanceof LivewireManager && method_exists($manager, 'componentNamespace')) {
            $manager->componentNamespace('App\\Livewire\\Game', 'game');
        }
    }
}
