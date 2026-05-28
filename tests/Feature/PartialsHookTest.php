<?php

use Illuminate\Support\Facades\View;
use Livewire\Component;
use PowerComponents\Partials\Attribute\PartialRender;
use PowerComponents\Partials\PartialsHook;

use function Livewire\store;

class DummyComponentForPartialsHook extends Component
{
    public function render()
    {
        return '<div>Dummy</div>';
    }

    #[PartialRender('dummy-partial', 'dummy-view')]
    protected function protectedIncrement()
    {
        // This is to hit the !isPublic() return early
    }
}

it('tests PartialsHook edge cases', function () {
    $hook = app(PartialsHook::class);
    $component = new DummyComponentForPartialsHook;

    $hook->setComponent($component);

    store($component)->set('forceRender', true);
    expect($hook->shouldForceRender())->toBeTrue();

    expect($hook->shouldSkipRender())->toBeFalse();

    store($component)->set('forceRender', false);
    expect($hook->shouldSkipRender())->toBeFalse();

    store($component)->set('partialFragments', [function () {
        return [];
    }]);

    expect($hook->shouldSkipRender())->toBeTrue();

    store($component)->set('isPendingPartialRender', false);

    $hook->renderPartial($component, function () {
        return [];
    });

    expect(store($component)->get('partialRendersCount'))->toBeNull();

    $hook->partial('my-partial', '<div>HTML String</div>');
    $fragments = store($component)->get('partialFragments');
    expect($fragments)->toBeArray();
});

it('tests PartialsHook call method with protected method', function () {
    $hook = app(PartialsHook::class);
    $component = new DummyComponentForPartialsHook;
    $hook->setComponent($component);

    View::addLocation(__DIR__.'/../views');

    $returnEarly = false;
    $closure = $hook->call('protectedIncrement', [], $returnEarly, null, null);

    expect($returnEarly)->toBeTrue();
});
