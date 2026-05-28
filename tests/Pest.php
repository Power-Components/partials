<?php

use Pest\Browser\Browsable;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

pest()->extend(TestCase::class, Browsable::class)
    ->in('Browser');

pest()->browser()
    ->inChrome()
    ->withHost('localhost');
