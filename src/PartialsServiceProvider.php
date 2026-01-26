<?php

namespace PowerComponents\Partials;

use Illuminate\Support\ServiceProvider;
use Livewire\Mechanisms\DataStore;

class PartialsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishConfigs();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../resources/config/powergrid-partials.php',
            'powergrid-partials'
        );

        if (! config('powergrid-partials.enabled')) {
            return;
        }

        app('livewire')->componentHook(PartialsHook::class);

        $this->app->singleton(DataStore::class, DataStoreOverride::class);
    }

    private function publishConfigs(): void
    {
        $this->publishes([
            __DIR__.'/../resources/config/powergrid-partials.php' => config_path('powergrid-partials.php'),
        ], 'powergrid-partials-config');
    }
}
