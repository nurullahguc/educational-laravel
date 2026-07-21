<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Treat every test request as originating from the SPA frontend so
        // Sanctum applies its stateful (cookie/session) middleware, exactly
        // like a real browser request from http://localhost:5173.
        $this->withHeader('Origin', 'http://localhost');
    }
}
