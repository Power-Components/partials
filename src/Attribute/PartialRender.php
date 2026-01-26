<?php

namespace PowerComponents\Partials\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class PartialRender
{
    public function __construct(
        public string $view,
        public string $partialName
    ) {}
}
