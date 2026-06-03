<?php

namespace Tests\Browser;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Livewire\Livewire;
use PowerComponents\Partials\Attribute\PartialRender;

class PerformanceTestComponent extends Component
{
    public int $count = 0;

    public array $data = [];

    public function mount()
    {
        for ($i = 0; $i < 100; $i++) {
            $this->data[] = "Row $i: ".str_repeat('Massive Data ', 10);
        }
    }

    #[PartialRender('performance-partial', 'count-partial')]
    public function incWithPartial()
    {
        $this->count++;
    }

    public function incWithoutPartial()
    {
        $this->count++;
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <button id="btn-partial" wire:click="incWithPartial">Partial Update</button>
                <button id="btn-full" wire:click="incWithoutPartial">Full Update</button>
                
                <div id="partial-target" wire:partial="count-partial">
                    @include('performance-partial', ['__partial' => $this])
                </div>

                <div id="large-payload" style="display:none">
                    @foreach($data as $row)
                        <p>{{ $row }}</p>
                    @endforeach
                </div>
            </div>
BLADE;
    }
}

beforeEach(function () {
    Livewire::component('perf-test-comp', PerformanceTestComponent::class);

    $viewPath = __DIR__.'/../views/performance-partial.blade.php';
    file_put_contents($viewPath, '<div id="partial-root">Count: {{ $__partial->count }} (ID: {{ uniqid() }})</div>');

    Route::get('/perf-test', fn () => Blade::render('
        <html>
            <head>@livewireStyles</head>
            <body>
                <livewire:perf-test-comp />
                @livewireScripts
                <script type="module" src="/powergrid-partials/partials.js"></script>
            </body>
        </html>
    '))->middleware('web');
});

it('measures the performance benefit of partial renders', function () {
    $page = $this->visit('/perf-test');

    $page->script(<<<'JS'
        () => {
            window.__lastEffects = null;
            window.__lastPayloadSize = null;
            Livewire.interceptMessage(({ message, onSuccess }) => {
                onSuccess(({ payload }) => {
                    window.__lastEffects = payload.effects;
                    window.__lastPayloadSize = JSON.stringify(payload).length;
                });
            });
        }
    JS);

    // 1. Partial Render
    $page->click('#btn-partial')->waitForEvent('networkidle');
    $page->assertScript('() => window.__lastEffects !== null');
    $partialSize = (int) $page->script('() => window.__lastPayloadSize');

    // Check that partial payload doesn't have the full HTML in effects
    $hasFullHtmlInPartial = $page->script('() => !!window.__lastEffects.html');
    expect($hasFullHtmlInPartial)->toBeFalse('Partial should NOT return full html');

    // Reset
    $page->script('() => { window.__lastEffects = null; window.__lastPayloadSize = null; }');

    // 2. Full Render
    $page->click('#btn-full')->waitForEvent('networkidle');
    $page->assertScript('() => window.__lastEffects !== null');
    $fullSize = (int) $page->script('() => window.__lastPayloadSize');

    // Check that full payload DOES have the full HTML
    $hasFullHtmlInFull = $page->script('() => !!window.__lastEffects.html');
    expect($hasFullHtmlInFull)->toBeTrue('Full render should return full html')
        ->and($partialSize)->toBeGreaterThan(10)
        ->and($fullSize)->toBeGreaterThan($partialSize);
});
