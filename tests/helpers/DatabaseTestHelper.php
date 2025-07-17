<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Events\QueryExecuted;

/**
 * Helper methods for database testing
 */
trait DatabaseTestHelper
{
    /**
     * Assert that a database query contains specific SQL
     */
    protected function assertQueryContains(string $sql, callable $callback): void
    {
        $queries = $this->recordQueries($callback);
        
        $found = false;
        foreach ($queries as $query) {
            if (str_contains($query['sql'], $sql)) {
                $found = true;
                break;
            }
        }
        
        $this->assertTrue($found, "Query containing '{$sql}' was not executed.");
    }

    /**
     * Assert number of queries executed
     */
    protected function assertQueryCount(int $expected, callable $callback): void
    {
        $queries = $this->recordQueries($callback);
        
        $this->assertCount(
            $expected,
            $queries,
            "Expected {$expected} queries, but {count($queries)} were executed."
        );
    }

    /**
     * Assert no N+1 queries
     */
    protected function assertNoNPlusOneQueries(callable $callback): void
    {
        $queries = $this->recordQueries($callback);
        
        $selectQueries = array_filter($queries, function ($query) {
            return str_starts_with(strtolower(trim($query['sql'])), 'select');
        });
        
        $duplicates = [];
        $tables = [];
        
        foreach ($selectQueries as $query) {
            // Extract table name from query
            preg_match('/from\s+`?(\w+)`?/i', $query['sql'], $matches);
            if (isset($matches[1])) {
                $table = $matches[1];
                if (!isset($tables[$table])) {
                    $tables[$table] = 0;
                }
                $tables[$table]++;
                
                if ($tables[$table] > 1) {
                    $duplicates[] = $query['sql'];
                }
            }
        }
        
        $this->assertEmpty(
            $duplicates,
            'N+1 query problem detected. Duplicate queries: ' . implode("\n", array_unique($duplicates))
        );
    }

    /**
     * Record database queries
     */
    protected function recordQueries(callable $callback): array
    {
        $queries = [];
        
        DB::listen(function (QueryExecuted $query) use (&$queries) {
            $queries[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ];
        });
        
        $callback();
        
        return $queries;
    }

    /**
     * Assert database transaction was used
     */
    protected function assertUsesTransaction(callable $callback): void
    {
        $usesTransaction = false;
        
        DB::listen(function (QueryExecuted $query) use (&$usesTransaction) {
            if (in_array(strtolower(trim($query->sql)), ['begin', 'start transaction'])) {
                $usesTransaction = true;
            }
        });
        
        $callback();
        
        $this->assertTrue($usesTransaction, 'Database transaction was not used.');
    }

    /**
     * Create database snapshot
     */
    protected function createDatabaseSnapshot(string $name): void
    {
        $tables = DB::select('SHOW TABLES');
        $snapshot = [];
        
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            $snapshot[$tableName] = DB::table($tableName)->get()->toArray();
        }
        
        file_put_contents(
            base_path("tests/snapshots/db_{$name}.json"),
            json_encode($snapshot, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Assert database matches snapshot
     */
    protected function assertDatabaseMatchesSnapshot(string $name): void
    {
        $snapshotPath = base_path("tests/snapshots/db_{$name}.json");
        
        if (!file_exists($snapshotPath)) {
            $this->createDatabaseSnapshot($name);
            $this->markTestIncomplete('Database snapshot created. Run test again to verify.');
        }
        
        $expected = json_decode(file_get_contents($snapshotPath), true);
        $tables = DB::select('SHOW TABLES');
        
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            $actual = DB::table($tableName)->get()->toArray();
            
            $this->assertEquals(
                $expected[$tableName] ?? [],
                $actual,
                "Table {$tableName} does not match snapshot."
            );
        }
    }

    /**
     * Assert soft deleted records exist
     */
    protected function assertSoftDeletedInDatabase(string $table, array $data): void
    {
        $count = DB::table($table)
            ->where($data)
            ->whereNotNull('deleted_at')
            ->count();
            
        $this->assertGreaterThan(0, $count, 'Soft deleted record not found in database.');
    }

    /**
     * Reset auto-increment counters
     */
    protected function resetAutoIncrements(array $tables = []): void
    {
        if (empty($tables)) {
            $tables = array_map(function ($table) {
                return array_values((array)$table)[0];
            }, DB::select('SHOW TABLES'));
        }
        
        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
        }
    }

    /**
     * Assert index exists on table
     */
    protected function assertIndexExists(string $table, string $indexName): void
    {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        $indexNames = array_column($indexes, 'Key_name');
        
        $this->assertContains(
            $indexName,
            $indexNames,
            "Index {$indexName} does not exist on table {$table}"
        );
    }

    /**
     * Profile database query
     */
    protected function profileQuery(callable $callback): array
    {
        DB::statement('SET profiling = 1');
        
        $callback();
        
        $profile = DB::select('SHOW PROFILE');
        DB::statement('SET profiling = 0');
        
        return $profile;
    }

    /**
     * Assert query uses index
     */
    protected function assertQueryUsesIndex(string $sql, array $bindings = []): void
    {
        $explain = DB::select("EXPLAIN {$sql}", $bindings);
        
        $usesIndex = false;
        foreach ($explain as $row) {
            if (!empty($row->key) && $row->key !== 'NULL') {
                $usesIndex = true;
                break;
            }
        }
        
        $this->assertTrue($usesIndex, 'Query does not use an index.');
    }

    /**
     * Create test database with specific schema version
     */
    protected function createTestDatabaseAtVersion(string $version): void
    {
        // Get all migrations up to specific version
        $migrations = collect(\File::files(database_path('migrations')))
            ->map(function ($file) {
                return $file->getFilenameWithoutExtension();
            })
            ->filter(function ($migration) use ($version) {
                return $migration <= $version;
            })
            ->sort();
            
        // Run migrations up to version
        foreach ($migrations as $migration) {
            require_once database_path("migrations/{$migration}.php");
            $class = \Str::studly(implode('_', array_slice(explode('_', $migration), 4)));
            (new $class)->up();
        }
    }
}