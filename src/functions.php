<?php

use Livewire\Component;
use PowerComponents\Partials\PartialsHook;

if (! function_exists('partials')) {
    function partials(?Component $component = null): PartialsHook
    {
        $partialsHook = app(PartialsHook::class);

        if ($component) {
            $partialsHook->setComponent($component);
        }

        return $partialsHook;
    }
}
