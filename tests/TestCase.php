<?php

namespace Tests;

use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PowerComponents\Partials\PartialsServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            PartialsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('app.key', 'base64:Hupx3yAySlyS9lS1k4pXFwZ7eWq2G4c/3gB5G4y7+o4=');

        $app['config']->set('powergrid-partials.enabled', true);

        $app['view']->addLocation(__DIR__.'/views');
    }
}
