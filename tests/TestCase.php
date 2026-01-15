<?php

namespace Tests {

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     *
     * CRITICAL SAFEGUARD: Prevents tests from running against production database.
     * Added after incident on 2026-01-15 where PHPUnit deleted all production data.
     *
     * Three-layer protection:
     * 1. assertNoCachedConfiguration() - BEFORE boot (most critical!)
     * 2. assertNotProductionEnvironment() - AFTER boot
     * 3. assertNotProductionDatabase() - AFTER boot
     */
    protected function setUp(): void
    {
        // LAYER 1: Check for cached config BEFORE Laravel boots
        // This is CRITICAL because cached config ignores phpunit.xml overrides!
        $this->assertNoCachedConfiguration();

        parent::setUp();

        // LAYER 2 & 3: Runtime checks after application boots
        $this->assertNotProductionEnvironment();
        $this->assertNotProductionDatabase();
    }

    /**
     * Ensure configuration is not cached.
     *
     * WHY THIS IS CRITICAL:
     * When config:cache is active, Laravel ignores phpunit.xml and .env.testing
     * and uses the cached .env values - which may point to PRODUCTION database!
     * This was the root cause of the 2026-01-15 incident.
     */
    private function assertNoCachedConfiguration(): void
    {
        // Construct path relative to tests/ directory (works before Laravel boots)
        $cachedConfigPath = dirname(__DIR__) . '/bootstrap/cache/config.php';

        if (file_exists($cachedConfigPath)) {
            throw new RuntimeException(
                '🚨 CRITICAL: Tests cannot run with cached configuration! ' .
                'Cached config ignores phpunit.xml DB_DATABASE override and may use PRODUCTION database. ' .
                'Run "php artisan config:clear" before running tests. ' .
                'This safeguard was added after the 2026-01-15 data loss incident.'
            );
        }
    }

    /**
     * Ensure we're not running in production environment.
     */
    private function assertNotProductionEnvironment(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                '🚨 CRITICAL: Tests cannot run in production environment! ' .
                'APP_ENV is set to "production". Aborting to prevent data loss.'
            );
        }
    }

    /**
     * Ensure we're not connected to the production database.
     */
    private function assertNotProductionDatabase(): void
    {
        $currentDatabase = config('database.connections.mysql.database');
        $productionDatabases = ['askproai_db', 'askproai_staging'];

        if (in_array($currentDatabase, $productionDatabases, true)) {
            throw new RuntimeException(
                '🚨 CRITICAL: Tests cannot run against production database! ' .
                "Currently connected to: {$currentDatabase}. " .
                'Check phpunit.xml DB_DATABASE setting. Aborting to prevent data loss.'
            );
        }
    }
}

}
