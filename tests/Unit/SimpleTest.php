<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SimpleTest extends TestCase
{
    #[Test]
    public function test_simple_addition()
    {
        $this->assertEquals(4, 2 + 2);
    }
}