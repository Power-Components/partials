<?php

namespace Tests\Browser;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Livewire\Livewire;
use PowerComponents\Partials\Attribute\PartialRender;

class TableLoadingBrowserTest extends Component
{
    public array $items = [];

    public string $search = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    public string $searchUniqid;

    public string $mainUniqid;

    public function mount(): void
    {
        $this->searchUniqid = uniqid('search-');
        $this->mainUniqid = uniqid('main-');

        $this->items = [
            ['id' => 1, 'name' => 'Product A', 'price' => 100.00, 'stock' => 10],
            ['id' => 2, 'name' => 'Product B', 'price' => 200.50, 'stock' => 5],
            ['id' => 3, 'name' => 'Product C', 'price' => 150.75, 'stock' => 15],
            ['id' => 4, 'name' => 'Product D', 'price' => 80.00, 'stock' => 20],
        ];
    }

    #[PartialRender('table-body', 'table-partial')]
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    #[PartialRender('table-body', 'table-partial')]
    public function updatedSearch(): void
    {
        // Trigger re-render when search is updated
    }

    public function getFilteredItems(): array
    {
        $items = $this->items;

        // Apply search filter
        if (! empty($this->search)) {
            $items = array_filter($items, function ($item) {
                return str_contains(strtolower($item['name']), strtolower($this->search));
            });
        }

        // Apply sorting
        usort($items, function ($a, $b) {
            $aVal = $a[$this->sortField];
            $bVal = $b[$this->sortField];

            if ($this->sortDirection === 'asc') {
                return $aVal <=> $bVal;
            }

            return $bVal <=> $aVal;
        });

        return $items;
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <div id="main-uniqid" style="display:none;">{{ $mainUniqid }}</div>
                
                <!-- Search input outside partial -->
                <div id="search-container">
                    <input 
                        type="text" 
                        id="search-input" 
                        wire:model.live="search" 
                        placeholder="Search products..."
                        data-uniqid="{{ $searchUniqid }}"
                    />
                    <span id="search-uniqid" style="display:none;">{{ $searchUniqid }}</span>
                </div>

                <!-- Table with partial rendering -->
                <table id="products-table">
                    <thead id="table-header">
                        <tr>
                            <th>
                                <button id="sort-name" wire:click="sortBy('name')" type="button">
                                    Name
                                    @if($sortField === 'name')
                                        <span id="sort-indicator-name">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </button>
                            </th>
                            <th>
                                <button id="sort-price" wire:click="sortBy('price')" type="button">
                                    Price
                                    @if($sortField === 'price')
                                        <span id="sort-indicator-price">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </button>
                            </th>
                            <th>
                                <button id="sort-stock" wire:click="sortBy('stock')" type="button">
                                    Stock
                                    @if($sortField === 'stock')
                                        <span id="sort-indicator-stock">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </button>
                            </th>
                        </tr>
                    </thead>
                    
                    <tbody wire:partial="table-partial">
                        @include('table-body', ['__partial' => $this])
                    </tbody>
                </table>
            </div>
BLADE;
    }
}

class TableWithLoadingBrowserTest extends Component
{
    public bool $isLoading = false;

    public array $items = [];

    public string $loadingUniqid;

    public function mount(): void
    {
        $this->loadingUniqid = uniqid('loading-');
        $this->items = [
            ['id' => 1, 'name' => 'Item A'],
            ['id' => 2, 'name' => 'Item B'],
            ['id' => 3, 'name' => 'Item C'],
        ];
    }

    #[PartialRender('loading-table-body', 'loading-partial')]
    public function loadMore(): void
    {
        $count = count($this->items);
        $this->items[] = ['id' => $count + 1, 'name' => 'Item '.chr(65 + $count)];
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <div id="loading-uniqid" style="display:none;">{{ $loadingUniqid }}</div>
                
                <div wire:partial="loading-partial">
                    @include('loading-table-body', ['__partial' => $this])
                </div>
                
                <button id="load-more" wire:click="loadMore" type="button">Load More</button>
            </div>
BLADE;
    }
}

class TableInlineFiltersBrowserTest extends Component
{
    public int $pageNumber = 1;

    public string $filterUniqid;

    public function mount(): void
    {
        $this->filterUniqid = uniqid('filter-');
    }

    public function nextPage(): void
    {
        $this->pageNumber++;

        partials($this)->partial('table-body', 'table-inline-filters-body');
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <table>
                    <tbody wire:partial="table-body">
                        @include('table-inline-filters-body', ['__partial' => $this])
                    </tbody>
                </table>
                <button id="next-page" wire:click="nextPage" type="button">Next</button>
            </div>
        BLADE;
    }
}

beforeEach(function () {
    Livewire::component('browser-table-component', TableLoadingBrowserTest::class);
    Livewire::component('browser-loading-component', TableWithLoadingBrowserTest::class);
    Livewire::component('browser-inline-filters-component', TableInlineFiltersBrowserTest::class);

    Route::get('/browser-table-test', fn () => Blade::render('
        <html>
            <head>
                <style>
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th button { cursor: pointer; background: none; border: none; width: 100%; text-align: left; }
                    #search-input { width: 100%; padding: 8px; margin-bottom: 10px; }
                    .loading { opacity: 0.5; pointer-events: none; }
                </style>
                @livewireStyles
            </head>
            <body>
                <livewire:browser-table-component />
                @livewireScripts
                <script type="module" src="/powergrid-partials/partials.js"></script>
            </body>
        </html>
    '))->middleware('web');

    Route::get('/browser-loading-test', fn () => Blade::render('
        <html>
            <head>
                <style>
                    table { border-collapse: collapse; width: 100%; }
                    td { border: 1px solid #ddd; padding: 8px; }
                    .loading { opacity: 0.5; }
                </style>
                @livewireStyles
            </head>
            <body>
                <livewire:browser-loading-component />
                @livewireScripts
                <script type="module" src="/powergrid-partials/partials.js"></script>
            </body>
        </html>
    '))->middleware('web');

    Route::get('/browser-inline-filters-test', fn () => Blade::render('
        <html>
            <head>
                <style>
                    table { border-collapse: collapse; width: 100%; }
                    td { border: 1px solid #ddd; padding: 8px; }
                </style>
                @livewireStyles
            </head>
            <body>
                <livewire:browser-inline-filters-component />
                @livewireScripts
                <script type="module" src="/powergrid-partials/partials.js"></script>
            </body>
        </html>
    '))->middleware('web');
});

it('does not re-render search input when sorting columns', function () {
    $page = $this->visit('/browser-table-test');

    // Get initial search uniqid
    $initialSearchUniqid = $page->text('#search-uniqid');
    $initialMainUniqid = $page->text('#main-uniqid');

    // Verify initial state
    expect($initialSearchUniqid)->not->toBeEmpty()
        ->and($initialMainUniqid)->not->toBeEmpty();

    // Click to sort by name
    $page->click('#sort-name')
        ->waitForEvent('networkidle');

    // Search input should NOT be re-rendered (uniqid should be the same)
    expect($page->text('#search-uniqid'))->toBe($initialSearchUniqid)
        ->and($page->text('#main-uniqid'))->toBe($initialMainUniqid);

    // Click to sort by price
    $page->click('#sort-price')
        ->waitForEvent('networkidle');

    // Search input should still NOT be re-rendered
    expect($page->text('#search-uniqid'))->toBe($initialSearchUniqid)
        ->and($page->text('#main-uniqid'))->toBe($initialMainUniqid);
});

it('preserves search input value when sorting', function () {
    $page = $this->visit('/browser-table-test');

    // Type in search input
    $page->type('#search-input', 'Product A')
        ->waitForEvent('networkidle');

    // Get search input value
    $searchValue = $page->script('() => document.querySelector("#search-input").value');
    expect($searchValue)->toBe('Product A');

    // Click to sort by name
    $page->click('#sort-name')
        ->waitForEvent('networkidle');

    // Search input value should be preserved
    $searchValue = $page->script('() => document.querySelector("#search-input").value');
    expect($searchValue)->toBe('Product A');
});

it('updates table content when sorting by different columns', function () {
    $page = $this->visit('/browser-table-test');

    $initialSearchUniqid = $page->text('#search-uniqid');
    $initialMainUniqid = $page->text('#main-uniqid');

    // Click to sort by price
    $page->click('#sort-price')
        ->waitForEvent('networkidle');

    // Verify table body was updated
    $tableRenderId1 = $page->text('tbody .table-render-id');
    expect($tableRenderId1)->not->toBeEmpty();

    // But search and main should NOT be updated
    expect($page->text('#search-uniqid'))->toBe($initialSearchUniqid)
        ->and($page->text('#main-uniqid'))->toBe($initialMainUniqid);

    // Click again to reverse sort
    $page->click('#sort-price')
        ->waitForEvent('networkidle');

    // Table body should have a NEW render id (was re-rendered)
    $tableRenderId2 = $page->text('tbody .table-render-id');
    expect($tableRenderId2)->not->toBe($tableRenderId1);

    // But search and main should STILL be the same
    expect($page->text('#search-uniqid'))->toBe($initialSearchUniqid)
        ->and($page->text('#main-uniqid'))->toBe($initialMainUniqid);
});

it('filters and sorts correctly together', function () {
    $page = $this->visit('/browser-table-test');

    $initialSearchUniqid = $page->text('#search-uniqid');

    // Type in search input
    $page->type('#search-input', 'Product')
        ->waitForEvent('networkidle');

    // All products should be visible (all contain "Product")
    // Count visible tr elements (excluding the hidden render-id row)
    $rowCount = $page->script('() => document.querySelectorAll("tbody tr[data-id]").length');
    expect($rowCount)->toBe(4);

    // Search uniqid should still be the same
    expect($page->text('#search-uniqid'))->toBe($initialSearchUniqid);

    // Now sort by stock
    $page->click('#sort-stock')
        ->waitForEvent('networkidle');

    // Check first product after sort (should be lowest stock)
    $firstProduct = $page->text('tbody tr[data-id]:first-of-type td:first-child');
    expect($firstProduct)->not->toBeEmpty();

    // Search input should still preserve its value and uniqid
    $searchValue = $page->script('() => document.querySelector("#search-input").value');
    expect($searchValue)->toBe('Product')
        ->and($page->text('#search-uniqid'))->toBe($initialSearchUniqid);
});

it('updates only table body when sorting, not the header', function () {
    $page = $this->visit('/browser-table-test');

    // Add data attribute to header to track if it re-renders
    $page->script('() => document.querySelector("#table-header").setAttribute("data-test-id", "original-header")');

    // Click to sort
    $page->click('#sort-name')
        ->waitForEvent('networkidle');

    // Header should still have the same data attribute
    $hasOriginalAttribute = $page->script('() => document.querySelector("#table-header").getAttribute("data-test-id") === "original-header"');
    expect($hasOriginalAttribute)->toBeTrue();
});

it('shows loading state during data load', function () {
    $page = $this->visit('/browser-loading-test');

    $initialLoadingUniqid = $page->text('#loading-uniqid');

    // Verify initial items exist
    $initialCount = $page->script('() => document.querySelectorAll("tbody tr[data-id]").length');
    expect($initialCount)->toBeGreaterThan(0);

    // Click load more
    $page->click('#load-more')
        ->waitForEvent('networkidle');

    // Loading uniqid should be the same (main container not re-rendered)
    expect($page->text('#loading-uniqid'))->toBe($initialLoadingUniqid);

    // Verify table body uniqid changed (partial was re-rendered)
    $tableBodyUniqid = $page->text('#table-body-uniqid');
    expect($tableBodyUniqid)->not->toBeEmpty();
});

it('table partial contains only table body in payload', function () {
    $page = $this->visit('/browser-table-test');

    $page->script(<<<'JS'
        () => {
            window.__livewirePartialPayload = null;
            Livewire.interceptMessage(({ message, onSuccess }) => {
                onSuccess(({ payload }) => {
                    window.__livewirePartialPayload = payload.effects;
                });
            });
        }
    JS);

    $page->click('#sort-name')
        ->waitForEvent('networkidle');

    $page->assertScript('() => window.__livewirePartialPayload !== null');

    $hasPartialFragments = $page->script(<<<'JS'
        () => {
            const effects = window.__livewirePartialPayload;
            if (!effects) return false;
            return !!(effects.partialFragments && Object.keys(effects.partialFragments).length > 0);
        }
    JS);

    $hasTablePartial = $page->script(<<<'JS'
        () => {
            const effects = window.__livewirePartialPayload;
            if (!effects?.partialFragments) return false;
            return 'table-partial' in effects.partialFragments;
        }
    JS);

    expect($hasPartialFragments)->toBeTrue()
        ->and($hasTablePartial)->toBeTrue();
});

it('search input maintains focus state across partial updates', function () {
    $page = $this->visit('/browser-table-test');

    // Focus on search input
    $page->click('#search-input');

    // Verify focus
    $hasFocus = $page->script('() => document.activeElement.id === "search-input"');
    expect($hasFocus)->toBeTrue();

    // Type and trigger search
    $page->type('#search-input', 'A')
        ->waitForEvent('networkidle');

    // Focus should be maintained after partial update
    $hasFocus = $page->script('() => document.activeElement.id === "search-input"');
    expect($hasFocus)->toBeTrue();
});

it('preserves data attributes on search input when sorting', function () {
    $page = $this->visit('/browser-table-test');

    // Get initial data-uniqid attribute
    $initialDataUniqid = $page->script('() => document.querySelector("#search-input").getAttribute("data-uniqid")');
    expect($initialDataUniqid)->not->toBeEmpty();

    // Sort by name
    $page->click('#sort-name')
        ->waitForEvent('networkidle');

    // Data attribute should be preserved
    $currentDataUniqid = $page->script('() => document.querySelector("#search-input").getAttribute("data-uniqid")');
    expect($currentDataUniqid)->toBe($initialDataUniqid);

    // Sort by price
    $page->click('#sort-price')
        ->waitForEvent('networkidle');

    // Data attribute should still be preserved
    $currentDataUniqid = $page->script('() => document.querySelector("#search-input").getAttribute("data-uniqid")');
    expect($currentDataUniqid)->toBe($initialDataUniqid);
});

it('demonstrates payload size difference between partial and full render', function () {
    $template = <<<'HTML'
        <html>
            <head>
                @livewireStyles
                <style>
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                </style>
            </head>
            <body>
                <livewire:browser-table-component />
                @livewireScripts
                <script type="module" src="/powergrid-partials/partials.js"></script>
            </body>
        </html>
HTML;

    Route::get('/perf-comparison-loading', fn () => Blade::render($template))->middleware('web');

    // Create a version without partials
    class TableStandardComparison extends TableLoadingBrowserTest
    {
        public function sortBy(string $field): void {}

        public function render()
        {
            return str_replace('wire:partial="table-partial"', '', parent::render());
        }
    }
    Livewire::component('standard-comparison', TableStandardComparison::class);

    Route::get('/perf-comparison-standard', fn () => Blade::render(str_replace('browser-table-component', 'standard-comparison', $template)))->middleware('web');

    // 1. Partial
    $page = $this->visit('/perf-comparison-loading');

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

    $page->click('#sort-name')->waitForEvent('networkidle');
    $page->assertScript('() => window.__payloadSize !== null');
    $partialSize = (int) $page->script('() => window.__payloadSize');

    // 2. Standard
    $page = $this->visit('/perf-comparison-standard');

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

    $page->click('#sort-name')->waitForEvent('networkidle');
    $page->assertScript('() => window.__payloadSize !== null');
    $fullSize = (int) $page->script('() => window.__payloadSize');

    expect($partialSize)->toBeGreaterThan(0)
        ->and($fullSize)->toBeGreaterThan($partialSize);
});

it('updates tbody rows while preserving an ignored inline-filter row', function () {
    $page = $this->visit('/browser-inline-filters-test')
        ->assertSeeIn('#page-cell', 'page-1');

    $filterBefore = $page->text('#filter-cell');

    $page->click('#next-page')
        ->waitForEvent('networkidle')
        ->assertSeeIn('#page-cell', 'page-2');

    expect($page->text('#filter-cell'))->toBe($filterBefore);
});
