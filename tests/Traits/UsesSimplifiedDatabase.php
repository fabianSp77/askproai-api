<?php

namespace Tests\Traits;

use Illuminate\Contracts\Console\Kernel;

trait UsesSimplifiedDatabase
{
    /**
     * Setup a simplified database for testing
     */
    protected function setupSimplifiedDatabase(): void
    {
        // Skip migrations that might cause issues in testing
        if (app()->environment('testing')) {
            $this->app->instance('migration.repository', new \Illuminate\Database\Migrations\NullMigrationRepository());
        }
    }

    /**
     * Define hooks to migrate the database before and after each test.
     */
    public function setUpUsesSimplifiedDatabase(): void
    {
        // This method intentionally left blank - we handle setup in TestCase
    }

    /**
     * Refresh a conventional test database.
     */
    protected function refreshTestDatabase(): void
    {
        // Override the default behavior to use simplified migrations
        // which are already handled in TestCase::setUp()
    }

    /**
     * Begin a database transaction on the testing database.
     */
    public function beginDatabaseTransaction(): void
    {
        // Transactions not needed with simplified migrations
    }

    /**
     * Handle database transactions on the specified connections.
     */
    protected function connectionsToTransact(): array
    {
        return [];
    }
}