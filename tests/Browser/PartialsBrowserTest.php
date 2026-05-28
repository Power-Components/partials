<?php

namespace Tests\Browser;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Livewire\Livewire;
use PowerComponents\Partials\Attribute\PartialRender;

class PartialsBrowserTest extends Component
{
    public int $count = 0;

    public string $mainId;

    public function mount(): void
    {
        $this->mainId = uniqid();
    }

    #[PartialRender('partial-count', 'count-partial')]
    public function increment(): void
    {
        $this->count++;
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <div id="main-id">Main: {{ $mainId }}</div>
                <div id="main-uniqid">Main Uniqid: {{ uniqid() }}</div>

                <div wire:partial="count-partial">
                    @include('partial-count', ['__partial' => $this])
                </div>

                <button id="increment" wire:click="increment">Increment</button>
            </div>
BLADE;
    }
}

class PartialsIgnoreBrowserTest extends Component
{
    public int $count = 0;

    #[PartialRender('partial-ignore', 'ignore-partial')]
    public function increment()
    {
        $this->count++;
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <div wire:partial="ignore-partial">
                    @include('partial-ignore', ['__partial' => $this])
                </div>
                <button id="increment" wire:click="increment">Increment</button>
            </div>
BLADE;
    }
}

class PartialsMultipleBrowserTest extends Component
{
    public int $countA = 0;

    public int $countB = 0;

    public string $mainUniqid;

    public function mount(): void
    {
        $this->mainUniqid = uniqid();
    }

    #[PartialRender('partial-count-a', 'partial-a')]
    public function incrementA(): void
    {
        $this->countA++;
    }

    #[PartialRender('partial-count-b', 'partial-b')]
    public function incrementB(): void
    {
        $this->countB++;
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <div id="main-uniqid">{{ $mainUniqid }}</div>

                <div wire:partial="partial-a">
                    @include('partial-count-a', ['__partial' => $this])
                </div>

                <div wire:partial="partial-b">
                    @include('partial-count-b', ['__partial' => $this])
                </div>

                <button id="increment-a" wire:click="incrementA">Increment A</button>
                <button id="increment-b" wire:click="incrementB">Increment B</button>
            </div>
BLADE;
    }
}

beforeEach(function () {
    Livewire::component('browser-partial-component', PartialsBrowserTest::class);
    Livewire::component('browser-ignore-component', PartialsIgnoreBrowserTest::class);
    Livewire::component('browser-multiple-component', PartialsMultipleBrowserTest::class);

    Route::get('/browser-test', fn () => Blade::render('
        <html>
            <head>@livewireStyles</head>
            <body>
                <livewire:browser-partial-component />
                @livewireScripts
                <script type="module" src="/powergrid-partials/partials.js"></script>
            </body>
        </html>
    '))->middleware('web');

    Route::get('/browser-ignore-test', fn () => Blade::render('
        <html>
            <head>@livewireStyles</head>
            <body>
                <livewire:browser-ignore-component />
                @livewireScripts
                <script type="module" src="/powergrid-partials/partials.js"></script>
            </body>
        </html>
    '))->middleware('web');

    Route::get('/browser-multiple-test', fn () => Blade::render('
        <html>
            <head>@livewireStyles</head>
            <body>
                <livewire:browser-multiple-component />
                @livewireScripts
                <script type="module" src="/powergrid-partials/partials.js"></script>
            </body>
        </html>
    '))->middleware('web');
});

it('updates only the partial fragment and preserves main content', function () {
    $page = $this->visit('/browser-test')
        ->assertSeeIn('#count', '0');

    $initialMainUniqid = $page->text('#main-uniqid');
    $initialPartialUniqid = $page->text('#partial-uniqid');

    $page->click('#increment')
        ->waitForEvent('networkidle')
        ->assertSeeIn('#count', '1');

    expect($page->text('#main-uniqid'))
        ->toBe($initialMainUniqid)
        ->and($page->text('#partial-uniqid'))->not->toBe($initialPartialUniqid);
});

it('accumulates state correctly across multiple increments', function () {
    $page = $this->visit('/browser-test')
        ->assertSeeIn('#count', '0');

    foreach (range(1, 3) as $expected) {
        $page->click('#increment')
            ->waitForEvent('networkidle')
            ->assertSeeIn('#count', (string) $expected);
    }
});

it('payload contains partialFragments effect and no html effect on partial render', function () {
    $page = $this->visit('/browser-test');

    $page->script(<<<'JS'
        () => {
            window.__livewireEffects = null;
            Livewire.interceptMessage(({ message, onSuccess }) => {
                onSuccess(({ payload }) => {
                    window.__livewireEffects = payload.effects;
                });
            });
        }
    JS);

    $page->click('#increment')
        ->waitForEvent('networkidle');

    $page->assertScript('() => window.__livewireEffects !== null');

    $hasPartialFragments = $page->script(<<<'JS'
        () => {
            const effects = window.__livewireEffects;
            if (!effects) return false;
            return !!(effects.partialFragments && Object.keys(effects.partialFragments).length > 0);
        }
    JS);

    $hasHtmlEffect = $page->script(<<<'JS'
        () => {
            const effects = window.__livewireEffects;
            if (!effects) return false;
            return !!effects.html;
        }
    JS);

    expect($hasPartialFragments)->toBeTrue()
        ->and($hasHtmlEffect)->toBeFalse();
});

it('payload partialFragments contains the correct partial name key', function () {
    $page = $this->visit('/browser-test');

    $page->script(<<<'JS'
        () => {
            window.__livewireEffects = null;
            Livewire.interceptMessage(({ message, onSuccess }) => {
                onSuccess(({ payload }) => {
                    window.__livewireEffects = payload.effects;
                });
            });
        }
    JS);

    $page->click('#increment')
        ->waitForEvent('networkidle');

    $page->assertScript('() => window.__livewireEffects !== null');

    $partialKeys = $page->script(<<<'JS'
        () => {
            const effects = window.__livewireEffects;
            if (!effects?.partialFragments) return [];
            return Object.keys(effects.partialFragments);
        }
    JS);

    expect($partialKeys)->toContain('count-partial');
});

it('preserves wire:partial.ignore content after partial update', function () {
    $page = $this->visit('/browser-ignore-test');

    $ignoredBefore = $page->text('#ignored-content');

    $page->click('#increment')
        ->waitForEvent('networkidle');

    expect($page->text('#ignored-content'))->toBe($ignoredBefore);
});

it('updates dynamic content inside partial while ignoring wire:partial.ignore block', function () {
    $page = $this->visit('/browser-ignore-test')
        ->assertSeeIn('#count', '0');

    $ignoredBefore = $page->text('#ignored-content');

    $page->click('#increment')
        ->waitForEvent('networkidle')
        ->assertSeeIn('#count', '1');

    expect($page->text('#ignored-content'))->toBe($ignoredBefore);
});

it('updates only the targeted partial when multiple partials exist', function () {
    $page = $this->visit('/browser-multiple-test')
        ->assertSeeIn('#count-a', '0')
        ->assertSeeIn('#count-b', '0');

    $uniqidABefore = $page->text('#partial-uniqid-a');
    $uniqidBBefore = $page->text('#partial-uniqid-b');
    $mainUniqid = $page->text('#main-uniqid');

    $page->click('#increment-a')
        ->waitForEvent('networkidle')
        ->assertSeeIn('#count-a', '1')
        ->assertSeeIn('#count-b', '0');

    expect($page->text('#partial-uniqid-a'))->not->toBe($uniqidABefore)
        ->and($page->text('#partial-uniqid-b'))->toBe($uniqidBBefore)
        ->and($page->text('#main-uniqid'))->toBe($mainUniqid);
});

it('updates each partial independently', function () {
    $page = $this->visit('/browser-multiple-test');

    $page->click('#increment-a')
        ->waitForEvent('networkidle')
        ->assertSeeIn('#count-a', '1')
        ->assertSeeIn('#count-b', '0');

    $page->click('#increment-b')
        ->waitForEvent('networkidle')
        ->assertSeeIn('#count-a', '1')
        ->assertSeeIn('#count-b', '1');

    $page->click('#increment-b')
        ->waitForEvent('networkidle')
        ->assertSeeIn('#count-a', '1')
        ->assertSeeIn('#count-b', '2');
});
