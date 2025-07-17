<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait DatabaseTestHelper
{
    /**
     * Assert database has record
     */
    protected function assertDatabaseHasTable(string $table): void
    {
        $this->assertTrue(
            Schema::hasTable($table),
            "Failed asserting that table [{$table}] exists."
        );
    }

    /**
     * Assert database count
     */
    protected function assertDatabaseTableCount(string $table, int $count, ?string $connection = null): void
    {
        $actualCount = DB::connection($connection)->table($table)->count();
        
        $this->assertEquals(
            $count,
            $actualCount,
            "Failed asserting that table [{$table}] has {$count} records. Found {$actualCount}."
        );
    }

    /**
     * Assert database has exact record
     */
    protected function assertDatabaseHasExact(string $table, array $data, ?string $connection = null): void
    {
        $exists = DB::connection($connection)
            ->table($table)
            ->where($data)
            ->exists();
            
        $this->assertTrue(
            $exists,
            "Failed asserting that table [{$table}] has record matching: " . json_encode($data)
        );
    }

    /**
     * Assert database missing exact record
     */
    protected function assertDatabaseMissingExact(string $table, array $data, ?string $connection = null): void
    {
        $exists = DB::connection($connection)
            ->table($table)
            ->where($data)
            ->exists();
            
        $this->assertFalse(
            $exists,
            "Failed asserting that table [{$table}] is missing record matching: " . json_encode($data)
        );
    }

    /**
     * Create database transaction savepoint
     */
    protected function createSavepoint(string $name = 'test'): void
    {
        DB::statement("SAVEPOINT {$name}");
    }

    /**
     * Rollback to database savepoint
     */
    protected function rollbackToSavepoint(string $name = 'test'): void
    {
        DB::statement("ROLLBACK TO SAVEPOINT {$name}");
    }

    /**
     * Assert soft deleted
     */
    protected function assertRecordSoftDeleted(string $table, array $data = [], ?string $connection = null, ?string $deletedAtColumn = 'deleted_at'): void
    {
        $query = DB::connection($connection)->table($table);
        
        foreach ($data as $key => $value) {
            $query->where($key, $value);
        }
        
        $record = $query->first();
        
        $this->assertNotNull(
            $record,
            "Failed asserting that record exists in table [{$table}]"
        );
        
        $this->assertNotNull(
            $record->{$deletedAtColumn},
            "Failed asserting that record in table [{$table}] is soft deleted"
        );
    }

    /**
     * Seed test data
     */
    protected function seedTestData(string $seeder, array $state = []): void
    {
        $seederClass = "\\Database\\Seeders\\{$seeder}";
        
        if (!class_exists($seederClass)) {
            $seederClass = $seeder;
        }
        
        app($seederClass)->run($state);
    }

    /**
     * Clear table
     */
    protected function clearTable(string $table, ?string $connection = null): void
    {
        DB::connection($connection)->table($table)->truncate();
    }

    /**
     * Disable foreign key checks
     */
    protected function withoutForeignKeyChecks(callable $callback)
    {
        Schema::disableForeignKeyConstraints();
        
        try {
            return $callback();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * Assert query count
     */
    protected function assertQueryCount(int $expected, callable $callback): void
    {
        $queries = collect();
        
        DB::listen(function ($query) use ($queries) {
            $queries->push($query);
        });
        
        $callback();
        
        $this->assertCount(
            $expected,
            $queries,
            "Expected {$expected} queries, but {$queries->count()} were executed."
        );
    }

    /**
     * Get last inserted ID
     */
    protected function getLastInsertedId(string $table, string $column = 'id')
    {
        return DB::table($table)->max($column);
    }

    /**
     * Assert index exists
     */
    protected function assertIndexExists(string $table, string $index): void
    {
        $indexes = collect(DB::select("SHOW INDEX FROM {$table}"))
            ->pluck('Key_name')
            ->unique()
            ->values();
            
        $this->assertContains(
            $index,
            $indexes->toArray(),
            "Failed asserting that index [{$index}] exists on table [{$table}]"
        );
    }

    /**
     * Assert column exists
     */
    protected function assertColumnExists(string $table, string $column): void
    {
        $this->assertTrue(
            Schema::hasColumn($table, $column),
            "Failed asserting that column [{$column}] exists on table [{$table}]"
        );
    }

    /**
     * Get table structure
     */
    protected function getTableStructure(string $table): array
    {
        return Schema::getColumnListing($table);
    }

    /**
     * Assert transaction is active
     */
    protected function assertTransactionActive(): void
    {
        $this->assertTrue(
            DB::transactionLevel() > 0,
            "Failed asserting that a database transaction is active"
        );
    }

    /**
     * Create test database dump
     */
    protected function createDatabaseDump(string $filename = 'test_dump.sql'): string
    {
        $path = storage_path("testing/{$filename}");
        $database = config('database.connections.mysql.database');
        
        exec("mysqldump {$database} > {$path}");
        
        return $path;
    }

    /**
     * Restore database from dump
     */
    protected function restoreDatabaseDump(string $path): void
    {
        $database = config('database.connections.mysql.database');
        
        exec("mysql {$database} < {$path}");
    }
}