# Livewire Partials

Livewire Partials provide a structured and explicit way to update **specific DOM fragments** of a Livewire component instead of re-rendering the entire component tree.  
This is especially useful for complex components such as data tables, where partial updates significantly improve performance and user experience.

---

## Requirements

- PHP 8.3+
- Livewire ^4.0

---

## Installation

```bash
composer require power-components/partials
```

---

## JavaScript Setup

Import the package JavaScript once in your application entrypoint.

**resources/js/app.js**
```javascript
import '../../../vendor/power-components/partials/resources/js/index.js'
```

---

## Configuration

You may disable partial rendering globally via environment configuration.

**.env**
```env
POWERGRID_PARTIALS_ENABLED=false
```

When disabled, Livewire behaves exactly as usual and no partial payloads are generated.

---

## Core Concepts

### What Is a Partial

A **partial** is a named DOM fragment explicitly marked for selective updates.  
Only the HTML associated with that fragment is re-rendered and sent to the frontend.

Partials are identified by a unique name and mapped to a view or raw HTML.

---

## View Structure

Partials work best when you extract your DOM regions into separate Blade files or components. This allows you to reuse the same view for both the initial render and subsequent partial updates.

**resources/views/components/table/index.blade.php**
```blade
<div>
    <table>
        <thead wire:partial="table-thead">
            <x-table.tr :__partial="$this" />
        </thead>

        <tbody wire:partial="table-tbody">
            <x-table.tbody :__partial="$this" />
        </tbody>
    </table>
</div>
```

### Understanding `$this` vs `$__partial`

When a component is rendered normally (initial page load or full Livewire update), the `$this` variable refers to the Livewire component instance.

However, during a **partial update**, the partial is rendered in isolation, and the package automatically injects the component instance into a variable named `$__partial`.

To make your sub-views (partials) compatible with both scenarios, you should:
1. Accept a `__partial` attribute in your Blade components (using `@props`).
2. Pass `:__partial="$this"` when including them in the main view.
3. Use `$__partial` inside the sub-view to access the component.

**resources/views/components/table/tbody.blade.php**
```blade
@props(['__partial'])

@foreach($__partial->users as $user)
    <tr>
        <td>{{ $user->id }}</td>
        <td>{{ $user->name }}</td>
    </tr>
@endforeach
```

---

## Component Usage

### Registering Partials Manually

Use the `partials()` helper to register one or more partials during a component action.

```php
public function sortBy(string $field): void
{
    $this->sortField = $field;

    partials($this)
        ->partial(
            'table-thead',
            'components.table.thead',
            [
                'tableName' => $this->tableName,
            ]
        )
        ->partial(
            'table-tbody',
            'components.table.tbody',
            [
                'tableName' => $this->tableName,
            ]
        );
}
```

The component instance is automatically available inside partial views as `$__partial`.

---

## Attribute-Based Partial Rendering

For simple scenarios, partials can be registered declaratively using the `PartialRender` attribute.

```php
use PowerComponents\Partials\Attribute\PartialRender;

#[PartialRender('components.table.tbody', 'table-tbody')]
public function sortBy(string $field): void
{
    $this->sortField = $field;
}
```

When the method is executed, the specified view is automatically rendered and dispatched as a partial update.

---

## Ignoring Elements (Preserving State)

You may exclude specific elements from being re-rendered during partial updates using `wire:partial.ignore`. This is particularly useful for elements that maintain their own internal state (like Alpine.js components, checkboxes, or focus) that would otherwise be lost if the element's HTML were replaced.

### Key Usage

This directive **must** be used on an element that is inside a `wire:partial` block.

For it to work correctly, the element **must** have a unique identification (key). The package attempts to automatically identify it via `wire:key` or `id` attributes. If neither is present, you **must** provide a custom key directly in the directive.

```blade
<thead wire:partial="table-thead">
    <tr>
        <!-- ✅ The ID ensures ignore works -->
        <th id="select-all-column" wire:partial.ignore>
            <input type="checkbox" x-model="selectAll" />
        </th>
        <th>Name</th>
    </tr>
</thead>

@foreach($users as $user)
    <div wire:key="user-{{ $user->id }}">
        <!-- ✅ Ignoring only the first element to preserve its state -->
        <div 
            @if($loop->first) wire:partial.ignore="first-user-bio" @endif
        >
            {{ $user->bio }}
        </div>
    </div>
@endforeach
```

Using explicit keys (e.g., `wire:partial.ignore="my-key"`) is the best practice as it makes the relationship between the ignored element and its state more predictable and robust.

### How it works

1. **Backend:** The package detects the `wire:partial.ignore` directive and replaces the element's content with a lightweight placeholder (`<!--PARTIAL:IGNORE:key-->`).
2. **Frontend:** Before applying the update, the JavaScript bridge captures the current `innerHTML` of the ignored element.
3. **Morphing:** During the DOM morphing process, the ignored element itself is skipped (it's not updated), and its original content is restored if necessary.
4. **Preservation:** Event listeners, local state, and Alpine.js data remain completely intact because the DOM element is never destroyed or replaced.

## Credits

- Created by [Luan Freitas](https://twitter.com/luanfreitasdev)

[Dan Harrin](https://github.com/danharrin) proposed the original concept of the partials.
The code for this package is based on the code he kindly shared with us.

<sup><b>Notice of Non-Affiliation and Disclaimer:</b> Partials are not affiliated with, associated with, endorsed by, or in any way officially connected with the <a href="https://laravel-livewire.com" target="_blank">Laravel Livewire</a> - copyright by Caleb Porzio. Laravel is a trademark of Taylor Otwell.</sup>
