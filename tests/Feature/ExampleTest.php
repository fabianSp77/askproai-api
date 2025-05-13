<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** @test */
    public function root_returns_not_found(): void
    {
        $this->get('/')->assertStatus(404);
    }
}
