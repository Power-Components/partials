<?php

use PowerComponents\Partials\PartialsHook;

if (! function_exists('partials')) {
    function partials(?Livewire\Component $component = null): PartialsHook
    {
        $partialsHook = app(PartialsHook::class);

        if ($component) {
            $partialsHook->setComponent($component);
        }

        return $partialsHook;
    }
}
