<?php

namespace Tests\Feature;

use Livewire\Component;
use Livewire\Livewire;
use PowerComponents\Partials\Attribute\PartialRender;

class PartialsTest extends Component
{
    public int $count = 0;

    #[PartialRender('partial-count', 'count-partial')]
    public function increment(): void
    {
        $this->count++;
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <div wire:partial="count-partial">
                    <span id="count">{{ $count }}</span>
                </div>
                <button id="increment" wire:click="increment">Increment</button>
            </div>
BLADE;
    }
}

it('can increment count via partial render', function () {
    Livewire::test(PartialsTest::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1);
});
