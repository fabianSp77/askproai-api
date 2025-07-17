<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\TestsWithMocks;

abstract class TestCase extends BaseTestCase
{
    use TestsWithMocks;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock external services by default
        $this->mockExternalServices();
    }
}