<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests don't need compiled front-end assets.
        $this->withoutVite();

        // Never call NHTSA for real; tests fake the responses they need.
        Http::preventStrayRequests();
    }
}
