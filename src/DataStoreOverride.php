<?php

namespace PowerComponents\Partials;

use Livewire\Livewire;
use Livewire\Mechanisms\DataStore;

class DataStoreOverride extends DataStore
{
    public function get($instance, $key, $default = null): mixed
    {
        if (! Livewire::isLivewireRequest()) {
            return parent::get($instance, $key, $default);
        }

        if ($key === 'skipRender') {
            try {
                $partialsHook = app(PartialsHook::class);

                if (! $partialsHook) {
                    return parent::get($instance, $key, $default);
                }

                $partialsHook->setComponent($instance);

                return $partialsHook->shouldSkipRender();
            } catch (\Throwable $e) {
                return parent::get($instance, $key, $default);
            }
        }

        return parent::get($instance, $key, $default);
    }

    public function set($instance, $key, $value)
    {
        if ($key === 'skipRender' && Livewire::isLivewireRequest()
        ) {
            return;
        }

        parent::set($instance, $key, $value);
    }
}
