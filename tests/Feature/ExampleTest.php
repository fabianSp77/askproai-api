<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** @test */

    #[Test]
    public function root_returns_not_found(): void
    {
        $response = $this->get('/');
        
        // Accept either 404 (not found) or 302 (redirect)
        $this->assertContains($response->status(), [404, 302]);
    }
}
