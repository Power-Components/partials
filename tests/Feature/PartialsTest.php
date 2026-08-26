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

class TableIgnorePartialsTest extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;

        partials($this)->partial('table-body', <<<HTML
<tbody wire:partial="table-body">
    <tr wire:partial.ignore="filters" wire:key="filters"><td id="filter">keep</td></tr>
    <tr wire:key="row"><td id="count">{$this->count}</td></tr>
</tbody>
HTML);
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <table>
                    <tbody wire:partial="table-body">
                        <tr wire:partial.ignore="filters" wire:key="filters"><td id="filter">keep</td></tr>
                        <tr wire:key="row"><td id="count">{{ $count }}</td></tr>
                    </tbody>
                </table>
                <button wire:click="increment" id="increment">+</button>
            </div>
        BLADE;
    }
}

it('emits tbody partialFragments without a full html effect', function () {
    $test = Livewire::test(TableIgnorePartialsTest::class)
        ->call('increment');

    expect(data_get($test->effects, 'html'))->toBeNull();

    $html = data_get($test->effects, 'partialFragments.table-body');

    expect($html)->toBeString()
        ->toContain('wire:partial="table-body"')
        ->toContain('PARTIAL:IGNORE:filters')
        ->toContain('id="count">1');
});
