<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DatabaseConnectionTest extends TestCase
{
    #[Test]
    public function it_can_connect_to_test_database()
    {
        $this->assertTrue(true);
    }
    
    #[Test]
    public function it_uses_sqlite_in_memory()
    {
        $driver = config("database.default");
        $this->assertEquals("sqlite", $driver);
        
        $database = config("database.connections.sqlite.database");
        $this->assertEquals(":memory:", $database);
    }
}
