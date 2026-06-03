<?php

namespace Tests\Browser;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Livewire\Livewire;
use PowerComponents\Partials\Attribute\PartialRender;

/**
 * Component WITH Partials (optimized)
 */
class TableWithPartials extends Component
{
    public array $items = [];

    public string $search = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    public function mount(): void
    {
        $this->items = $this->generateItems(100);
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

        if (! empty($this->search)) {
            $items = array_filter($items, function ($item) {
                return str_contains(strtolower($item['name']), strtolower($this->search));
            });
        }

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

    private function generateItems(int $count): array
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $items[] = [
                'id' => $i,
                'name' => 'Product '.chr(65 + ($i % 26)).$i,
                'price' => rand(50, 500) + (rand(0, 99) / 100),
                'stock' => rand(0, 100),
            ];
        }

        return $items;
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <!-- Navigation / Breadcrumbs -->
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="/dashboard">Dashboard</a></li>
                        <li><a href="/products">Products</a></li>
                        <li><span>All Products</span></li>
                    </ol>
                </nav>

                <!-- Page Header with Stats -->
                <div class="page-header">
                    <h1>Products Table (With Partials)</h1>
                    <p class="description">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Products</h3>
                        <p class="stat-value">{{ count($items) }}</p>
                        <p class="stat-description">Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem.</p>
                    </div>
                    <div class="stat-card">
                        <h3>In Stock</h3>
                        <p class="stat-value">{{ count(array_filter($items, fn($i) => $i['stock'] > 0)) }}</p>
                        <p class="stat-description">Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.</p>
                    </div>
                    <div class="stat-card">
                        <h3>Out of Stock</h3>
                        <p class="stat-value">{{ count(array_filter($items, fn($i) => $i['stock'] === 0)) }}</p>
                        <p class="stat-description">Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore.</p>
                    </div>
                    <div class="stat-card">
                        <h3>Average Price</h3>
                        <p class="stat-value">${{ number_format(array_sum(array_column($items, 'price')) / count($items), 2) }}</p>
                        <p class="stat-description">Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur.</p>
                    </div>
                </div>

                <!-- Sidebar Filters -->
                <div class="layout-container">
                    <aside class="sidebar">
                        <div class="filter-group">
                            <h4>Categories</h4>
                            <ul>
                                <li><label><input type="checkbox" checked> Electronics (45)</label></li>
                                <li><label><input type="checkbox" checked> Clothing (32)</label></li>
                                <li><label><input type="checkbox"> Home & Garden (28)</label></li>
                                <li><label><input type="checkbox"> Sports (19)</label></li>
                                <li><label><input type="checkbox"> Books (15)</label></li>
                                <li><label><input type="checkbox"> Automotive (12)</label></li>
                                <li><label><input type="checkbox"> Health & Beauty (22)</label></li>
                                <li><label><input type="checkbox"> Toys & Games (18)</label></li>
                            </ul>
                            <p class="filter-help">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet.</p>
                        </div>
                        <div class="filter-group">
                            <h4>Price Range</h4>
                            <div class="price-inputs">
                                <input type="number" placeholder="Min" value="0">
                                <span>-</span>
                                <input type="number" placeholder="Max" value="1000">
                            </div>
                            <p class="filter-help">Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arci eget nulla. Class aptent taciti sociosqu ad litora torquent per conubia nostra.</p>
                        </div>
                        <div class="filter-group">
                            <h4>Availability</h4>
                            <ul>
                                <li><label><input type="radio" name="stock" checked> All Items</label></li>
                                <li><label><input type="radio" name="stock"> In Stock Only</label></li>
                                <li><label><input type="radio" name="stock"> Out of Stock</label></li>
                                <li><label><input type="radio" name="stock"> Low Stock (< 5)</label></li>
                            </ul>
                            <p class="filter-help">Curabitur sodales ligula in libero. Sed dignissim lacinia nunc. Curabitur tortor. Pellentesque nibh. Aenean quam. In scelerisque sem at dolor.</p>
                        </div>
                        <div class="filter-group">
                            <h4>Ratings</h4>
                            <ul>
                                <li><label><input type="checkbox"> ★★★★★ (5 stars)</label></li>
                                <li><label><input type="checkbox"> ★★★★☆ (4+ stars)</label></li>
                                <li><label><input type="checkbox"> ★★★☆☆ (3+ stars)</label></li>
                                <li><label><input type="checkbox"> ★★☆☆☆ (2+ stars)</label></li>
                            </ul>
                            <p class="filter-help">Maecenas mattis. Sed convallis tristique sem. Proin ut ligula vel nunc egestas porttitor. Morbi lectus risus, iaculis vel, suscipit quis, luctus non, massa.</p>
                        </div>
                    </aside>

                    <main class="content">
                        <!-- Search -->
                        <div class="search-bar">
                            <input 
                                type="text" 
                                id="search-input" 
                                wire:model.live="search" 
                                placeholder="Search products..."
                            />
                            <p class="search-help">Fusce ac turpis quis ligula lacinia aliquet. Mauris ipsum. Nulla metus metus, ullamcorper vel, tincidunt sed, euismod in, nibh. Quisque volutpat condimentum velit.</p>
                        </div>

                        <!-- Table -->
                        <table id="products-table">
                            <thead>
                                <tr>
                                    <th>
                                        <button id="sort-name" wire:click="sortBy('name')" type="button">
                                            Name
                                            @if($sortField === 'name')
                                                <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </button>
                                    </th>
                                    <th>
                                        <button id="sort-price" wire:click="sortBy('price')" type="button">
                                            Price
                                            @if($sortField === 'price')
                                                <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </button>
                                    </th>
                                    <th>
                                        <button id="sort-stock" wire:click="sortBy('stock')" type="button">
                                            Stock
                                            @if($sortField === 'stock')
                                                <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            
                            <tbody wire:partial="table-partial">
                                @include('table-body', ['__partial' => $this])
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="pagination">
                            <span>Showing 1-100 of {{ count($items) }} results</span>
                            <nav>
                                <button disabled>Previous</button>
                                <button class="active">1</button>
                                <button>2</button>
                                <button>3</button>
                                <button>Next</button>
                            </nav>
                        </div>
                    </main>
                </div>

                <!-- Footer -->
                <footer class="table-footer">
                    <div class="footer-info">
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus lacinia odio vitae vestibulum vestibulum. Cras venenatis euismod malesuada. Nulla facilisi. Etiam non diam ante. Duis rutrum diam sit amet urna elementum, a congue nunc faucibus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.</p>
                        <p>Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi.</p>
                        <p>Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus.</p>
                    </div>
                    <div class="footer-links">
                        <a href="/export/csv">Export CSV</a>
                        <a href="/export/pdf">Export PDF</a>
                        <a href="/export/excel">Export Excel</a>
                        <a href="/print">Print</a>
                    </div>
                </footer>
            </div>
BLADE;
    }
}

/**
 * Component WITHOUT Partials (standard Livewire)
 */
class TableWithoutPartials extends Component
{
    public array $items = [];

    public string $search = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    public function mount(): void
    {
        $this->items = $this->generateItems(100);
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void {}

    public function getFilteredItems(): array
    {
        $items = $this->items;

        if (! empty($this->search)) {
            $items = array_filter($items, function ($item) {
                return str_contains(strtolower($item['name']), strtolower($this->search));
            });
        }

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

    private function generateItems(int $count): array
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $items[] = [
                'id' => $i,
                'name' => 'Product '.chr(65 + ($i % 26)).$i,
                'price' => rand(50, 500) + (rand(0, 99) / 100),
                'stock' => rand(0, 100),
            ];
        }

        return $items;
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <!-- Navigation / Breadcrumbs -->
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="/dashboard">Dashboard</a></li>
                        <li><a href="/products">Products</a></li>
                        <li><span>All Products</span></li>
                    </ol>
                </nav>

                <!-- Page Header with Stats -->
                <div class="page-header">
                    <h1>Products Table (Without Partials)</h1>
                    <p class="description">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Products</h3>
                        <p class="stat-value">{{ count($items) }}</p>
                        <p class="stat-description">Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem.</p>
                    </div>
                    <div class="stat-card">
                        <h3>In Stock</h3>
                        <p class="stat-value">{{ count(array_filter($items, fn($i) => $i['stock'] > 0)) }}</p>
                        <p class="stat-description">Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.</p>
                    </div>
                    <div class="stat-card">
                        <h3>Out of Stock</h3>
                        <p class="stat-value">{{ count(array_filter($items, fn($i) => $i['stock'] === 0)) }}</p>
                        <p class="stat-description">Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore.</p>
                    </div>
                    <div class="stat-card">
                        <h3>Average Price</h3>
                        <p class="stat-value">${{ number_format(array_sum(array_column($items, 'price')) / count($items), 2) }}</p>
                        <p class="stat-description">Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur.</p>
                    </div>
                </div>

                <!-- Sidebar Filters -->
                <div class="layout-container">
                    <aside class="sidebar">
                        <div class="filter-group">
                            <h4>Categories</h4>
                            <ul>
                                <li><label><input type="checkbox" checked> Electronics (45)</label></li>
                                <li><label><input type="checkbox" checked> Clothing (32)</label></li>
                                <li><label><input type="checkbox"> Home & Garden (28)</label></li>
                                <li><label><input type="checkbox"> Sports (19)</label></li>
                                <li><label><input type="checkbox"> Books (15)</label></li>
                                <li><label><input type="checkbox"> Automotive (12)</label></li>
                                <li><label><input type="checkbox"> Health & Beauty (22)</label></li>
                                <li><label><input type="checkbox"> Toys & Games (18)</label></li>
                            </ul>
                            <p class="filter-help">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet.</p>
                        </div>
                        <div class="filter-group">
                            <h4>Price Range</h4>
                            <div class="price-inputs">
                                <input type="number" placeholder="Min" value="0">
                                <span>-</span>
                                <input type="number" placeholder="Max" value="1000">
                            </div>
                            <p class="filter-help">Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arci eget nulla. Class aptent taciti sociosqu ad litora torquent per conubia nostra.</p>
                        </div>
                        <div class="filter-group">
                            <h4>Availability</h4>
                            <ul>
                                <li><label><input type="radio" name="stock" checked> All Items</label></li>
                                <li><label><input type="radio" name="stock"> In Stock Only</label></li>
                                <li><label><input type="radio" name="stock"> Out of Stock</label></li>
                                <li><label><input type="radio" name="stock"> Low Stock (< 5)</label></li>
                            </ul>
                            <p class="filter-help">Curabitur sodales ligula in libero. Sed dignissim lacinia nunc. Curabitur tortor. Pellentesque nibh. Aenean quam. In scelerisque sem at dolor.</p>
                        </div>
                        <div class="filter-group">
                            <h4>Ratings</h4>
                            <ul>
                                <li><label><input type="checkbox"> ★★★★★ (5 stars)</label></li>
                                <li><label><input type="checkbox"> ★★★★☆ (4+ stars)</label></li>
                                <li><label><input type="checkbox"> ★★★☆☆ (3+ stars)</label></li>
                                <li><label><input type="checkbox"> ★★☆☆☆ (2+ stars)</label></li>
                            </ul>
                            <p class="filter-help">Maecenas mattis. Sed convallis tristique sem. Proin ut ligula vel nunc egestas porttitor. Morbi lectus risus, iaculis vel, suscipit quis, luctus non, massa.</p>
                        </div>
                    </aside>

                    <main class="content">
                        <!-- Search -->
                        <div class="search-bar">
                            <input 
                                type="text" 
                                id="search-input" 
                                wire:model.live="search" 
                                placeholder="Search products..."
                            />
                            <p class="search-help">Fusce ac turpis quis ligula lacinia aliquet. Mauris ipsum. Nulla metus metus, ullamcorper vel, tincidunt sed, euismod in, nibh. Quisque volutpat condimentum velit.</p>
                        </div>

                        <!-- Table -->
                        <table id="products-table">
                            <thead>
                                <tr>
                                    <th>
                                        <button id="sort-name" wire:click="sortBy('name')" type="button">
                                            Name
                                            @if($sortField === 'name')
                                                <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </button>
                                    </th>
                                    <th>
                                        <button id="sort-price" wire:click="sortBy('price')" type="button">
                                            Price
                                            @if($sortField === 'price')
                                                <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </button>
                                    </th>
                                    <th>
                                        <button id="sort-stock" wire:click="sortBy('stock')" type="button">
                                            Stock
                                            @if($sortField === 'stock')
                                                <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            
                            <tbody>
                                @foreach($this->getFilteredItems() as $item)
                                    <tr data-id="{{ $item['id'] }}">
                                        <td>{{ $item['name'] }}</td>
                                        <td>${{ number_format($item['price'], 2) }}</td>
                                        <td>{{ $item['stock'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="pagination">
                            <span>Showing 1-100 of {{ count($items) }} results</span>
                            <nav>
                                <button disabled>Previous</button>
                                <button class="active">1</button>
                                <button>2</button>
                                <button>3</button>
                                <button>Next</button>
                            </nav>
                        </div>
                    </main>
                </div>

                <!-- Footer -->
                <footer class="table-footer">
                    <div class="footer-info">
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus lacinia odio vitae vestibulum vestibulum. Cras venenatis euismod malesuada. Nulla facilisi. Etiam non diam ante. Duis rutrum diam sit amet urna elementum, a congue nunc faucibus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.</p>
                        <p>Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi.</p>
                        <p>Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus.</p>
                    </div>
                    <div class="footer-links">
                        <a href="/export/csv">Export CSV</a>
                        <a href="/export/pdf">Export PDF</a>
                        <a href="/export/excel">Export Excel</a>
                        <a href="/print">Print</a>
                    </div>
                </footer>
            </div>
BLADE;
    }
}

beforeEach(function () {
    Livewire::component('table-with-partials', TableWithPartials::class);
    Livewire::component('table-without-partials', TableWithoutPartials::class);
});

it('demonstrates payload size difference between WITH and WITHOUT partials', function () {
    $template = <<<'HTML'
        <html>
            <head>
                @livewireStyles
                <style>
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th button { cursor: pointer; background: none; border: none; }
                </style>
            </head>
            <body>
                <livewire:table-with-partials />
                @livewireScripts
                <script type="module" src="/powergrid-partials/partials.js"></script>
            </body>
        </html>
HTML;

    Route::get('/perf-with', fn () => Blade::render($template))->middleware('web');
    Route::get('/perf-without', fn () => Blade::render(str_replace('table-with-partials', 'table-without-partials', $template)))->middleware('web');

    // 1. WITH Partials - capture payload size
    $pageWith = $this->visit('/perf-with');

    $pageWith->script(<<<'JS'
        () => {
            window.__payloadSize = null;
            Livewire.interceptMessage(({ message, onSuccess }) => {
                onSuccess(({ payload }) => {
                    window.__payloadSize = JSON.stringify(payload).length;
                });
            });
        }
    JS);

    $pageWith->click('#sort-name')->waitForEvent('networkidle');
    $pageWith->assertScript('() => window.__payloadSize !== null');
    $partialSize = (int) $pageWith->script('() => window.__payloadSize');

    // 2. WITHOUT Partials - capture payload size
    $pageWithout = $this->visit('/perf-without');

    $pageWithout->script(<<<'JS'
        () => {
            window.__payloadSize = null;
            Livewire.interceptMessage(({ message, onSuccess }) => {
                onSuccess(({ payload }) => {
                    window.__payloadSize = JSON.stringify(payload).length;
                });
            });
        }
    JS);

    $pageWithout->click('#sort-name')->waitForEvent('networkidle');
    $pageWithout->assertScript('() => window.__payloadSize !== null');
    $fullSize = (int) $pageWithout->script('() => window.__payloadSize');

    // Assertions
    expect($partialSize)->toBeGreaterThan(0)
        ->and($fullSize)->toBeGreaterThan($partialSize);

    $savings = $fullSize - $partialSize;
    $savingsPercent = round(($savings / $fullSize) * 100, 1);

    echo "\n\n";
    echo "╔══════════════════════════════════════════════════════╗\n";
    echo "║   Payload Size: WITH vs WITHOUT Partials            ║\n";
    echo "╠══════════════════════════════════════════════════════╣\n";
    echo "║                                                      ║\n";
    echo '║  WITHOUT Partials: '.str_pad(number_format($fullSize).' bytes', 30)."  ║\n";
    echo '║  WITH Partials:    '.str_pad(number_format($partialSize).' bytes', 30)."  ║\n";
    echo "║                                                      ║\n";
    echo '║  Savings: '.str_pad(number_format($savings)." bytes ({$savingsPercent}% reduction)", 39)."  ║\n";
    echo "║                                                      ║\n";
    echo "╚══════════════════════════════════════════════════════╝\n";
    echo "\n";
});

it('partials preserve DOM elements outside the partial region', function () {
    Route::get('/perf-preserve', fn () => Blade::render('
        <html>
            <head>
                @livewireStyles
                <style>
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ddd; padding: 8px; }
                    th button { cursor: pointer; background: none; border: none; }
                </style>
            </head>
            <body>
                <livewire:table-with-partials />
                @livewireScripts
                <script type="module" src="/powergrid-partials/partials.js"></script>
            </body>
        </html>
    '))->middleware('web');

    $page = $this->visit('/perf-preserve');

    // Store reference to H1 element
    $page->script('() => { window.__h1Element = document.querySelector("h1"); }');

    // Sort triggers partial render
    $page->click('#sort-name')->waitForEvent('networkidle');

    // H1 element should be the exact same DOM node (not replaced)
    $sameElement = $page->script('() => window.__h1Element === document.querySelector("h1")');
    expect($sameElement)->toBeTrue('H1 element should be the same DOM reference after partial update');
});

it('without partials sends full HTML, with partials sends only fragment', function () {
    $template = <<<'HTML'
        <html>
            <head>
                @livewireStyles
                <style>
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ddd; padding: 8px; }
                    th button { cursor: pointer; background: none; border: none; }
                </style>
            </head>
            <body>
                <livewire:table-with-partials />
                @livewireScripts
                <script type="module" src="/powergrid-partials/partials.js"></script>
            </body>
        </html>
HTML;

    Route::get('/perf-fragment-with', fn () => Blade::render($template))->middleware('web');
    Route::get('/perf-fragment-without', fn () => Blade::render(str_replace('table-with-partials', 'table-without-partials', $template)))->middleware('web');

    // WITH Partials - should have partialFragments in payload
    $pageWith = $this->visit('/perf-fragment-with');

    $pageWith->script(<<<'JS'
        () => {
            window.__effects = null;
            Livewire.interceptMessage(({ message, onSuccess }) => {
                onSuccess(({ payload }) => {
                    window.__effects = payload.effects;
                });
            });
        }
    JS);

    $pageWith->click('#sort-name')->waitForEvent('networkidle');
    $pageWith->assertScript('() => window.__effects !== null');

    $hasPartialFragments = $pageWith->script(<<<'JS'
        () => {
            const effects = window.__effects;
            return !!(effects.partialFragments && Object.keys(effects.partialFragments).length > 0);
        }
    JS);

    $hasFullHtml = $pageWith->script(<<<'JS'
        () => {
            const effects = window.__effects;
            return !!effects.html;
        }
    JS);

    expect($hasPartialFragments)->toBeTrue('WITH partials should return partialFragments')
        ->and($hasFullHtml)->toBeFalse('WITH partials should NOT return full html');

    // WITHOUT Partials - should have full HTML in payload
    $pageWithout = $this->visit('/perf-fragment-without');

    $pageWithout->script(<<<'JS'
        () => {
            window.__effects = null;
            Livewire.interceptMessage(({ message, onSuccess }) => {
                onSuccess(({ payload }) => {
                    window.__effects = payload.effects;
                });
            });
        }
    JS);

    $pageWithout->click('#sort-name')->waitForEvent('networkidle');
    $pageWithout->assertScript('() => window.__effects !== null');

    $hasFullHtmlWithout = $pageWithout->script(<<<'JS'
        () => {
            const effects = window.__effects;
            return !!effects.html;
        }
    JS);

    expect($hasFullHtmlWithout)->toBeTrue('WITHOUT partials should return full html');
});
