<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class MigrationTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /** @test */
    public function all_required_tables_exist()
    {
        $requiredTables = [
            'companies',
            'branches',
            'staff',
            'services',
            'customers',
            'appointments',
            'calls',
            'call_charges',
            'portal_users',
            'prepaid_balances',
            'balance_topups',
            'calcom_event_types',
            'staff_event_types',
            'company_goals',
            'goal_metrics',
            'notifications',
            'webhook_events',
            'api_call_logs',
        ];

        foreach ($requiredTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table {$table} does not exist"
            );
        }
    }

    /** @test */
    public function companies_table_has_all_required_columns()
    {
        $requiredColumns = [
            'id',
            'name',
            'slug',
            'phone_number',
            'email',
            'address',
            'is_active',
            'prepaid_balance',
            'billing_rate_per_minute',
            'retell_api_key',
            'retell_agent_id',
            'calcom_api_key',
            'calcom_team_id',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('companies', $column),
                "Column {$column} does not exist in companies table"
            );
        }
    }

    /** @test */
    public function appointments_table_has_proper_foreign_keys()
    {
        $foreignKeys = DB::select("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'appointments'
            AND TABLE_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        $expectedForeignKeys = [
            'company_id' => 'companies',
            'branch_id' => 'branches',
            'customer_id' => 'customers',
            'staff_id' => 'staff',
            'service_id' => 'services',
        ];

        foreach ($expectedForeignKeys as $column => $referencedTable) {
            $found = false;
            foreach ($foreignKeys as $fk) {
                if ($fk->COLUMN_NAME === $column && $fk->REFERENCED_TABLE_NAME === $referencedTable) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Foreign key for {$column} referencing {$referencedTable} not found");
        }
    }

    /** @test */
    public function indexes_exist_for_performance_critical_columns()
    {
        $criticalIndexes = [
            'companies' => ['slug'],
            'branches' => ['company_id', 'slug'],
            'customers' => ['company_id', 'email', 'phone_number'],
            'appointments' => ['company_id', 'branch_id', 'appointment_datetime', 'status'],
            'calls' => ['company_id', 'phone_number', 'created_at'],
            'portal_users' => ['company_id', 'email'],
        ];

        foreach ($criticalIndexes as $table => $columns) {
            foreach ($columns as $column) {
                $indexes = DB::select("
                    SHOW INDEX FROM {$table} 
                    WHERE Column_name = ?
                ", [$column]);

                $this->assertNotEmpty(
                    $indexes,
                    "Index for {$column} in {$table} table not found"
                );
            }
        }
    }

    /** @test */
    public function unique_constraints_are_properly_set()
    {
        $uniqueConstraints = [
            'companies' => ['slug'],
            'portal_users' => ['email'],
            'customers' => ['company_id', 'email'],
            'branches' => ['company_id', 'slug'],
        ];

        foreach ($uniqueConstraints as $table => $columns) {
            if (count($columns) === 1) {
                // Single column unique
                $constraint = DB::select("
                    SHOW INDEX FROM {$table}
                    WHERE Column_name = ?
                    AND Non_unique = 0
                ", [$columns[0]]);

                $this->assertNotEmpty(
                    $constraint,
                    "Unique constraint for {$columns[0]} in {$table} not found"
                );
            } else {
                // Composite unique
                $constraint = DB::select("
                    SELECT COUNT(*) as count
                    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
                    JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                    ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                    WHERE tc.TABLE_NAME = ?
                    AND tc.CONSTRAINT_TYPE = 'UNIQUE'
                    AND kcu.COLUMN_NAME IN (?, ?)
                    AND tc.TABLE_SCHEMA = DATABASE()
                    GROUP BY tc.CONSTRAINT_NAME
                    HAVING COUNT(*) = ?
                ", [$table, $columns[0], $columns[1], count($columns)]);

                $this->assertNotEmpty(
                    $constraint,
                    "Composite unique constraint for " . implode(', ', $columns) . " in {$table} not found"
                );
            }
        }
    }

    /** @test */
    public function default_values_are_set_correctly()
    {
        $defaultValues = [
            'companies' => [
                'is_active' => '1',
                'prepaid_balance' => '0.00',
            ],
            'appointments' => [
                'status' => 'scheduled',
            ],
            'calls' => [
                'status' => 'pending',
            ],
            'portal_users' => [
                'is_active' => '1',
                'role' => 'user',
            ],
        ];

        foreach ($defaultValues as $table => $columns) {
            foreach ($columns as $column => $expectedDefault) {
                $columnInfo = DB::select("
                    SELECT COLUMN_DEFAULT
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_NAME = ?
                    AND COLUMN_NAME = ?
                    AND TABLE_SCHEMA = DATABASE()
                ", [$table, $column]);

                if (!empty($columnInfo)) {
                    $actualDefault = trim($columnInfo[0]->COLUMN_DEFAULT, "'");
                    $this->assertEquals(
                        $expectedDefault,
                        $actualDefault,
                        "Default value for {$column} in {$table} is incorrect"
                    );
                }
            }
        }
    }

    /** @test */
    public function migrations_are_idempotent()
    {
        // Run migrations
        Artisan::call('migrate:fresh');
        
        // Get initial state
        $initialTables = DB::select("SHOW TABLES");
        $initialCount = count($initialTables);

        // Run migrations again
        Artisan::call('migrate');

        // Get final state
        $finalTables = DB::select("SHOW TABLES");
        $finalCount = count($finalTables);

        // Should have same number of tables
        $this->assertEquals($initialCount, $finalCount);
    }

    /** @test */
    public function rollback_works_correctly()
    {
        // Get current batch
        $currentBatch = DB::table('migrations')->max('batch');

        // Run rollback
        Artisan::call('migrate:rollback');

        // Check that migrations were rolled back
        $remainingBatch = DB::table('migrations')->max('batch');

        $this->assertLessThan($currentBatch, $remainingBatch ?? 0);

        // Re-run migrations
        Artisan::call('migrate');
    }

    /** @test */
    public function column_types_are_appropriate()
    {
        $columnTypes = [
            'companies' => [
                'prepaid_balance' => 'decimal',
                'billing_rate_per_minute' => 'decimal',
            ],
            'calls' => [
                'duration' => 'integer',
                'cost' => 'decimal',
            ],
            'appointments' => [
                'appointment_datetime' => 'datetime',
                'duration_minutes' => 'integer',
            ],
        ];

        foreach ($columnTypes as $table => $columns) {
            foreach ($columns as $column => $expectedType) {
                $columnInfo = DB::select("
                    SELECT DATA_TYPE
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_NAME = ?
                    AND COLUMN_NAME = ?
                    AND TABLE_SCHEMA = DATABASE()
                ", [$table, $column]);

                if (!empty($columnInfo)) {
                    $actualType = $columnInfo[0]->DATA_TYPE;
                    $this->assertStringContainsString(
                        $expectedType,
                        $actualType,
                        "Column {$column} in {$table} has incorrect type"
                    );
                }
            }
        }
    }

    /** @test */
    public function soft_delete_columns_exist_where_needed()
    {
        $softDeleteTables = [
            'companies',
            'customers',
            'staff',
            'appointments',
            'portal_users',
        ];

        foreach ($softDeleteTables as $table) {
            $this->assertTrue(
                Schema::hasColumn($table, 'deleted_at'),
                "Soft delete column missing in {$table}"
            );
        }
    }

    /** @test */
    public function json_columns_are_properly_defined()
    {
        $jsonColumns = [
            'calls' => ['metadata', 'dynamic_variables'],
            'webhook_events' => ['payload', 'response'],
            'api_call_logs' => ['request_data', 'response_data'],
            'notifications' => ['data'],
        ];

        foreach ($jsonColumns as $table => $columns) {
            foreach ($columns as $column) {
                $columnInfo = DB::select("
                    SELECT DATA_TYPE
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_NAME = ?
                    AND COLUMN_NAME = ?
                    AND TABLE_SCHEMA = DATABASE()
                ", [$table, $column]);

                if (!empty($columnInfo)) {
                    $this->assertEquals(
                        'json',
                        $columnInfo[0]->DATA_TYPE,
                        "Column {$column} in {$table} should be JSON type"
                    );
                }
            }
        }
    }
}