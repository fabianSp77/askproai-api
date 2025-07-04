<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BasicPHPUnitTest extends TestCase
{
    #[Test]
    public function test_basic_addition()
    {
        $this->assertEquals(4, 2 + 2);
    }
    
    #[Test]
    public function test_basic_string()
    {
        $this->assertEquals('hello world', 'hello ' . 'world');
    }
}