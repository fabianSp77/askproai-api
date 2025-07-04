<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\Traits\SimplifiedMigrations;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use SimplifiedMigrations;
    
    /**
     * Force use of simplified migrations to avoid SQLite compatibility issues
     */
    protected $useSimplifiedMigrations = true;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable connection pooling for tests
        Config::set('database.connections.mysql.pooling', false);
        Config::set('database.connections.sqlite.pooling', false);
        Config::set('database.pool.enabled', false);
        
        // Configure SQLite for tests
        if (DB::getDriverName() === "sqlite") {
            try {
                DB::unprepared("PRAGMA foreign_keys=OFF");
                // Skip synchronous pragma as it can't be changed in transaction
                DB::unprepared("PRAGMA journal_mode=MEMORY");
                DB::unprepared("PRAGMA temp_store=MEMORY");
            } catch (\Exception $e) {
                // Ignore pragma errors in SQLite
            }
        }
        
        try {
            // Always use simplified migrations to avoid SQLite compatibility issues
            $this->runSimplifiedMigrations();
        } catch (\Exception $e) {
            // Log migration errors but continue
            error_log("Migration error in test setup: " . $e->getMessage());
            
            // Try minimal table setup
            $this->setupMinimalTables();
        }
        
        // Re-enable foreign keys
        if (DB::getDriverName() === "sqlite") {
            try {
                DB::unprepared("PRAGMA foreign_keys=ON");
            } catch (\Exception $e) {
                // Ignore pragma errors
            }
        }
    }
    
    protected function tearDown(): void
    {
        try {
            // Disable foreign keys for cleanup
            if (DB::getDriverName() === "sqlite") {
                try {
                    DB::unprepared("PRAGMA foreign_keys=OFF");
                } catch (\Exception $e) {
                    // Ignore pragma errors
                }
            }
            
            // Clean up database using simplified method
            $this->dropSimplifiedTables();
        } catch (\Exception $e) {
            // Ignore teardown errors
            error_log("Teardown error: " . $e->getMessage());
        }
        
        parent::tearDown();
    }
    
    /**
     * Setup minimal tables for basic tests
     */
    protected function setupMinimalTables(): void
    {
        // Create only essential tables
        DB::statement('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT, created_at TIMESTAMP, updated_at TIMESTAMP)');
        DB::statement('CREATE TABLE IF NOT EXISTS companies (id INTEGER PRIMARY KEY, name TEXT, created_at TIMESTAMP, updated_at TIMESTAMP)');
        DB::statement('CREATE TABLE IF NOT EXISTS failed_jobs (id INTEGER PRIMARY KEY, uuid TEXT, connection TEXT, queue TEXT, payload TEXT, exception TEXT, failed_at TIMESTAMP)');
    }
}
