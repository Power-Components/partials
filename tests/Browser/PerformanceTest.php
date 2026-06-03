<?php

namespace Tests\Browser;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Livewire\Livewire;
use PowerComponents\Partials\Attribute\PartialRender;

class PerformanceDemoComponent extends Component
{
    public int $count = 0;

    public array $data = [];

    public function mount()
    {
        for ($i = 0; $i < 100; $i++) {
            $this->data[] = "Row $i: ".str_repeat('Data ', 10);
        }
    }

    #[PartialRender('performance-partial', 'count-partial')]
    public function incWithPartial(): void
    {
        $this->count++;
    }

    public function incWithoutPartial(): void
    {
        $this->count++;
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <button id="btn-partial" wire:click="incWithPartial">Inc Partial</button>
                <button id="btn-full" wire:click="incWithoutPartial">Inc Full</button>
                
                <div wire:partial="count-partial">
                    @include('performance-partial', ['__partial' => $this])
                </div>

                <div style="display:none">
                    @foreach($data as $row)
                        <div>{{ $row }}</div>
                    @endforeach
                </div>
            </div>
BLADE;
    }
}

beforeEach(function () {
    Livewire::component('performance-demo', PerformanceDemoComponent::class);

    $viewPath = __DIR__.'/../views/performance-partial.blade.php';
    file_put_contents($viewPath, '<div id="count-value">Count: {{ $__partial->count }} (Uniqid: {{ uniqid() }})</div>');

    Route::get('/perf-demo', fn () => Blade::render('
        <html>
            <head>@livewireStyles</head>
            <body>
                <div id="metrics">Size: <span id="size">0</span></div>
                <livewire:performance-demo />
                @livewireScripts
                <script type="module" src="/powergrid-partials/partials.js"></script>
            </body>
        </html>
    '))->middleware('web');
});

it('proves partials are smaller', function () {
    $page = $this->visit('/perf-demo');

    // Register interceptor via script() after page load
    $page->script(<<<'JS'
        () => {
            window.__payloadSize = null;
            Livewire.interceptMessage(({ message, onSuccess }) => {
                onSuccess(({ payload }) => {
                    window.__payloadSize = JSON.stringify(payload).length;
                });
            });
        }
    JS);

    // Test Partial
    $page->click('#btn-partial')->waitForEvent('networkidle');
    $page->assertScript('() => window.__payloadSize !== null');
    $partialSize = (int) $page->script('() => window.__payloadSize');
    $countValue = $page->text('#count-value');

    expect($countValue)->toContain('Count: 1')
        ->and($partialSize)->toBeGreaterThan(0);

    // Reset payload size
    $page->script('() => { window.__payloadSize = null; }');

    // Test Full
    $page->click('#btn-full')->waitForEvent('networkidle');
    $page->assertScript('() => window.__payloadSize !== null');
    $fullSize = (int) $page->script('() => window.__payloadSize');
    $countValue = $page->text('#count-value');

    expect($countValue)->toContain('Count: 2')
        ->and($fullSize)->toBeGreaterThan($partialSize);
});
