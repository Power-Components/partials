<?php

use Livewire\Component;
use PowerComponents\Partials\DataStoreOverride;
use PowerComponents\Partials\PartialsHook;

class DummyComponentForDataStore extends Component
{
    public function render()
    {
        return '<div>Dummy</div>';
    }
}

it('tests DataStoreOverride when not a livewire request', function () {
    $store = new DataStoreOverride;
    $instance = new DummyComponentForDataStore;

    $store->set($instance, 'skipRender', 'some-value');
    expect($store->get($instance, 'skipRender'))->toBe('some-value');
});

it('tests DataStoreOverride during livewire request', function () {
    $store = new DataStoreOverride;
    $instance = new DummyComponentForDataStore;

    request()->headers->set('X-Livewire', 'true');

    $store->set($instance, 'skipRender', 'some-value');

    app()->bind(PartialsHook::class, function () {
        throw new Exception('Test exception');
    });

    expect($store->get($instance, 'skipRender'))->toBeNull();

    request()->headers->remove('X-Livewire');
    app()->forgetInstance(PartialsHook::class);
});
