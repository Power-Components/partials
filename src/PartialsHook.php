<?php

namespace PowerComponents\Partials;

use Closure;
use Illuminate\View\View;
use Livewire\{Component, ComponentHook};
use Livewire\Drawer\Utils;
use Livewire\Mechanisms\HandleComponents\{ComponentContext, ViewContext};
use PowerComponents\Partials\Attribute\PartialRender;
use ReflectionMethod;

use function Livewire\store;

class PartialsHook extends ComponentHook
{
    public function mount(): void
    {
        $this->storeSet('updatesCount', 0);
        $this->storeSet('callsCount', 0);
        $this->storeSet('partialRendersCount', 0);
        $this->storeSet('isPendingPartialRender', false);
    }

    public function hydrate(): void
    {
        $this->storeSet('updatesCount', 0);
        $this->storeSet('callsCount', 0);
        $this->storeSet('partialRendersCount', 0);
        $this->storeSet('isPendingPartialRender', false);

        store($this->component)->unset('partialFragments');
        store($this->component)->unset('skipRender');
    }

    public function dehydrate(ComponentContext $context): void
    {
        if ($this->shouldForceRender()) {
            return;
        }

        $partials = [];

        $storedPartials = $this->storeGet('partialFragments') ?? [];

        if (filled($storedPartials)) {
            foreach ($storedPartials as $renderPartials) {
                foreach ($renderPartials() as $partialName => $view) {
                    $html = null;

                    if (is_string($view)) {
                        $html = $view;
                    } else {
                        $revertSharingComponentWithViews = Utils::shareWithViews('__livewire', $this->component);

                        $viewContext = app(ViewContext::class);

                        $html = $view->render(function (View $view) use ($viewContext): void {
                            if (! array_key_exists('__partial', $view->getData())) {
                                $view->with('__partial', $this->component);
                            }

                            $viewContext->extractFromEnvironment($view->getFactory());
                        });

                        $revertSharingComponentWithViews();
                    }

                    if ($html === null) {
                        continue;
                    }

                    $html = preg_replace_callback(
                        '/(<(?<tag>[a-z0-9]+)\s+[^>]*?wire:partial\.ignore(?:\s*=\s*["\'](?<value>.*?)["\'])?[^>]*>)(.*?)(<\/\k<tag>>)/is',
                        function ($matches) {
                            $tagWithAttributes = $matches[1];
                            $attrValue = $matches['value'] ?? '';
                            $closingTag = $matches[5];

                            $key = $attrValue;
                            if (empty($key)) {
                                if (preg_match('/wire:key="([^"]+)"/', $tagWithAttributes, $keyMatches)) {
                                    $key = $keyMatches[1];
                                } elseif (preg_match('/id="([^"]+)"/', $tagWithAttributes, $idMatches)) {
                                    $key = $idMatches[1];
                                }
                            }

                            if (empty($key)) {
                                return $matches[0];
                            }

                            return $tagWithAttributes."<!--PARTIAL:IGNORE:{$key}-->".$closingTag;
                        },
                        $html
                    );

                    if (! str_contains($html, "wire:partial=\"{$partialName}\"")) {
                        $html = Utils::insertAttributesIntoHtmlRoot($html, [
                            'wire:partial' => $partialName,
                        ]);
                    }

                    $html = preg_replace([
                        '/\n/',
                        '/[ ]{2,}/',
                        '/ >/',
                    ], [
                        '',
                        ' ',
                        '>',
                    ], $html);

                    $partials[$partialName] = $html;
                }
            }
        }

        if (filled($partials)) {
            $context->addEffect('partialFragments', $partials);
        }
    }

    public function shouldForceRender(): bool
    {
        return store($this->component)->get('forceRender', false);
    }

    public function shouldSkipRender(): bool
    {
        try {
            $hasPartials = ! empty($this->storeGet('partialFragments') ?? []);

            if ($this->shouldForceRender()) {
                return false;
            }

            if (! $hasPartials) {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function update($propertyName, $fullPath, $newValue): Closure
    {
        $this->storeSet('updatesCount', ($this->storeGet('updatesCount') ?? 0) + 1);

        return fn () => $this->storeSet('isPendingPartialRender', true);
    }

    public function call($method, $params, &$returnEarly, $metadata, $componentContext): Closure
    {
        $this->storeSet('callsCount', ($this->storeGet('callsCount') ?? 0) + 1);
        $this->storeSet('isPendingPartialRender', true);

        if (method_exists($this->component, $method)) {
            $reflection = new ReflectionMethod($this->component, $method);
            $attributes = $reflection->getAttributes(PartialRender::class);

            if (filled($attributes)) {
                foreach ($attributes as $attribute) {
                    /** @var PartialRender $instance */
                    $instance = $attribute->newInstance();

                    $this->renderPartial($this->component, fn () => [
                        $instance->partialName => view($instance->view, ['__partial' => $this->component]),
                    ]);
                }

                if (! $reflection->isPublic()) {
                    $returnEarly = true;

                    return fn () => null;
                }
            }
        }

        return fn () => null;
    }

    public function partial(string $name, string|View $view, array $data = [], ?Component $component = null): static
    {
        if ($component) {
            $this->setComponent($component);
        }

        if ($this->component === null) {
            try {
                $this->setComponent(app('livewire')->current());
            } catch (\Throwable) {
                // If we can't get the current component, we'll fail later if needed,
                // but let's see if we can at least avoid the TypeError if it's still null.
            }
        }

        if ($this->component === null) {
            return $this;
        }

        if (is_string($view)) {
            try {
                $view = view($view, array_merge($data, ['__partial' => $this->component]));
            } catch (\InvalidArgumentException $e) {
                // If the view doesn't exist, we assume it's already a rendered HTML string.
            }
        } elseif (filled($data)) {
            $view->with(array_merge($data, ['__partial' => $this->component]));
        }

        if ($view instanceof View && ! array_key_exists('__partial', $view->getData())) {
            $view->with('__partial', $this->component);
        }

        $this->renderPartial($this->component, fn () => [
            $name => $view,
        ]);

        return $this;
    }

    public function renderPartial(Component $component, Closure $renderUsing): void
    {
        store($component)->push('partialFragments', $renderUsing);

        $this->recordPartialRender($component);
    }

    protected function recordPartialRender(Component $component): void
    {
        $isPending = store($component)->get('isPendingPartialRender', false);

        if (! $isPending) {
            return;
        }

        store($component)->set('partialRendersCount', (store($component)->get('partialRendersCount') ?? 0) + 1);
        store($component)->set('isPendingPartialRender', false);
    }
}
