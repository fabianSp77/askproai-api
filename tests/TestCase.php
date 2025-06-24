<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable foreign key checks for SQLite
        if (DB::getDriverName() === "sqlite") {
            DB::statement("PRAGMA foreign_keys=OFF");
        }
        
        // Run migrations without foreign key constraints
        Artisan::call("migrate:fresh", ["--force" => true]);
        
        // Re-enable foreign keys
        if (DB::getDriverName() === "sqlite") {
            DB::statement("PRAGMA foreign_keys=ON");
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up
        Artisan::call("migrate:reset", ["--force" => true]);
        
        parent::tearDown();
    }
}
