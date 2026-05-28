<?php

namespace PowerComponents\Partials;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Mechanisms\DataStore;

class PartialsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishConfigs();
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        Route::get('/powergrid-partials/partials.js', function () {
            return response(file_get_contents(__DIR__.'/../resources/js/partials.js'), 200, [
                'Content-Type' => 'application/javascript',
            ]);
        });

        Route::get('/powergrid-partials/utils.js', function () {
            return response(file_get_contents(__DIR__.'/../resources/js/utils.js'), 200, [
                'Content-Type' => 'application/javascript',
            ]);
        });
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
