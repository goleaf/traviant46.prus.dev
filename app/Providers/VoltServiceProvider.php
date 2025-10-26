<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt;
use Livewire\Volt\VoltManager;

/**
 * Service provider responsible for configuring Volt to use the application's shared layout and view directories.
 */
class VoltServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        /**
         * Ensure we only attempt to set a global layout when the installed Volt version exposes the helper.
         *
         * @var VoltManager|null $manager
         */
        $manager = Volt::getFacadeRoot();

        if ($manager instanceof VoltManager && method_exists($manager, 'layout')) {
            Volt::layout('layouts.app');
        }

        Volt::mount([
            config('livewire.view_path', resource_path('views/livewire')),
            resource_path('views/pages'),
        ]);
    }
}
