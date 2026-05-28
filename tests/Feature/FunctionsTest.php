<?php

use Livewire\Component;
use PowerComponents\Partials\PartialsHook;

class DummyComponentForFunctions extends Component
{
    public function render()
    {
        return '<div>Dummy</div>';
    }
}

it('test functions.php partials helper', function () {
    $hook = partials();
    expect($hook)->toBeInstanceOf(PartialsHook::class);

    $component = new DummyComponentForFunctions;
    $hook2 = partials($component);
    expect($hook2)->toBeInstanceOf(PartialsHook::class);
});
