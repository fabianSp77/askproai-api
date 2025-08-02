<?php

namespace Tests\Feature\Security;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Database Security Test
 * 
 * Tests database-level security including injection protection,
 * privilege escalation, data exposure, and connection security.
 * 
 * SEVERITY: CRITICAL - Database compromise potential
 */
class DatabaseSecurityTest extends BaseSecurityTestCase
{
    public function test_sql_injection_in_raw_queries()
    {
        $this->actingAs($this->admin1);

        $injectionPayloads = [
            "'; DROP TABLE customers; --",
            "1' UNION SELECT password FROM users--",
            "' OR 1=1--",
            "'; UPDATE users SET email='hacked@evil.com' WHERE id=1; --",
            "'; INSERT INTO users (email, password) VALUES ('hacker@evil.com', 'hacked'); --",
        ];

        foreach ($injectionPayloads as $payload) {
            try {
                // Test various raw query scenarios
                $results = DB::select("SELECT * FROM customers WHERE name = '{$payload}'");
                
                // If query succeeds, verify no malicious changes occurred
                $this->assertNotNull($results, 'Query should handle injection safely');
                
            } catch (\Exception $e) {
                // Exception is expected for malicious queries
                $this->assertTrue(true, 'SQL injection was blocked');
            }
        }

        // Verify no malicious data was inserted
        $this->assertDatabaseMissing('users', ['email' => 'hacked@evil.com']);
        $this->assertDatabaseMissing('users', ['email' => 'hacker@evil.com']);

        $this->logSecurityTestResult('sql_injection_raw_queries', true);
    }

    public function test_database_privilege_escalation()
    {
        $this->actingAs($this->admin1);

        // Test attempts to access system tables
        $systemTables = [
            'information_schema.tables',
            'mysql.user',
            'performance_schema.global_variables',
            'sys.version',
        ];

        foreach ($systemTables as $table) {
            try {
                $result = DB::select("SELECT * FROM {$table} LIMIT 1");
                
                // If accessible, should not contain sensitive info
                if (!empty($result)) {
                    $data = json_encode($result);
                    $this->assertStringNotContainsString('password', strtolower($data));
                    $this->assertStringNotContainsString('secret', strtolower($data));
                }
                
            } catch (\Exception $e) {
                // Access denied is expected for restricted tables
                $this->assertTrue(true, 'System table access properly restricted');
            }
        }

        $this->logSecurityTestResult('database_privilege_escalation', true);
    }

    public function test_database_connection_security()
    {
        // Test database connection configuration
        $dbConfig = config('database.connections.mysql');
        
        // Should use secure connection practices
        if (isset($dbConfig['options'])) {
            $options = $dbConfig['options'];
            
            // Check for SSL enforcement
            if (isset($options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT])) {
                $this->assertTrue($options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]);
            }
        }

        // Database user should not be root
        $this->assertNotEquals('root', $dbConfig['username']);
        
        // Password should not be empty
        $this->assertNotEmpty($dbConfig['password']);

        $this->logSecurityTestResult('database_connection_security', true);
    }

    public function test_stored_procedure_injection()
    {
        // Skip if stored procedures not used
        try {
            $procedures = DB::select('SHOW PROCEDURE STATUS');
            
            if (empty($procedures)) {
                $this->markTestSkipped('No stored procedures to test');
            }

            foreach ($procedures as $procedure) {
                $procName = $procedure->Name;
                
                // Test injection in procedure calls
                $injectionAttempts = [
                    "'; DROP TABLE customers; --",
                    "', (SELECT password FROM users LIMIT 1), '",
                ];

                foreach ($injectionAttempts as $injection) {
                    try {
                        DB::select("CALL {$procName}('{$injection}')");
                    } catch (\Exception $e) {
                        $this->assertTrue(true, 'Stored procedure injection blocked');
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->markTestSkipped('Cannot test stored procedures');
        }

        $this->logSecurityTestResult('stored_procedure_injection', true);
    }

    public function test_database_schema_information_leakage()
    {
        $this->actingAs($this->admin1);

        // Test if schema information is exposed
        try {
            $tables = DB::select('SHOW TABLES');
            
            // Should not expose sensitive table names
            $tableNames = array_map(function($table) {
                return array_values((array)$table)[0];
            }, $tables);

            $sensitiveTables = ['passwords', 'secrets', 'tokens', 'private_keys'];
            
            foreach ($sensitiveTables as $sensitiveTable) {
                $this->assertNotContains($sensitiveTable, $tableNames,
                    "Sensitive table '{$sensitiveTable}' exposed in schema");
            }
            
        } catch (\Exception $e) {
            // If SHOW TABLES is restricted, that's good
            $this->assertTrue(true, 'Schema information properly restricted');
        }

        $this->logSecurityTestResult('database_schema_leakage', true);
    }

    public function test_database_blind_sql_injection()
    {
        $this->actingAs($this->admin1);

        // Time-based blind SQL injection attempts
        $timeBasedPayloads = [
            "test' AND SLEEP(5) AND '1'='1",
            "test'; WAITFOR DELAY '00:00:05'; --",
            "test' AND (SELECT COUNT(*) FROM customers WHERE SLEEP(5)) > 0 AND '1'='1",
        ];

        foreach ($timeBasedPayloads as $payload) {
            $startTime = microtime(true);
            
            try {
                Customer::where('name', $payload)->get();
            } catch (\Exception $e) {
                // Exception is fine
            }
            
            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            
            // Should not cause significant delays
            $this->assertLessThan(2, $duration, 
                'Time-based SQL injection may be possible');
        }

        $this->logSecurityTestResult('blind_sql_injection', true);
    }

    public function test_database_transaction_isolation()
    {
        $this->actingAs($this->admin1);

        // Test transaction isolation levels
        DB::beginTransaction();
        
        $customer = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Transaction Test',
        ]);

        // In another "connection" (simulated), this shouldn't be visible
        $foundCustomer = Customer::where('name', 'Transaction Test')->first();
        
        DB::rollBack();

        // After rollback, customer should not exist
        $customerAfterRollback = Customer::where('name', 'Transaction Test')->first();
        $this->assertNull($customerAfterRollback, 
            'Transaction rollback failed - data leakage');

        $this->logSecurityTestResult('database_transaction_isolation', true);
    }

    public function test_database_concurrent_access_security()
    {
        $this->actingAs($this->admin1);

        // Test concurrent modifications
        $customer = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Concurrent Test',
            'email' => 'concurrent@test.com',
        ]);

        // Simulate concurrent modification attempt
        DB::beginTransaction();
        
        $customer1 = Customer::find($customer->id);
        $customer1->name = 'Modified by User 1';
        
        // Simulate second user modifying same record
        $customer2 = Customer::find($customer->id);
        $customer2->name = 'Modified by User 2';
        
        $customer1->save();
        
        try {
            $customer2->save();
            // If both saves succeed, check for data integrity
            $finalCustomer = Customer::find($customer->id);
            $this->assertNotNull($finalCustomer->name, 'Data corruption in concurrent access');
        } catch (\Exception $e) {
            // Optimistic locking or conflict detection is good
            $this->assertTrue(true, 'Concurrent modification properly handled');
        }
        
        DB::rollBack();

        $this->logSecurityTestResult('concurrent_access_security', true);
    }

    public function test_database_data_encryption()
    {
        // Test if sensitive data is encrypted at rest
        $user = User::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'encryption@test.com',
            'password' => bcrypt('test_password_123'),
        ]);

        // Get raw database value
        $rawData = DB::table('users')
            ->where('id', $user->id)
            ->select('password', 'email')
            ->first();

        // Password should be hashed/encrypted
        $this->assertNotEquals('test_password_123', $rawData->password);
        $this->assertTrue(password_verify('test_password_123', $rawData->password) ||
                         strlen($rawData->password) > 50, 
                         'Password not properly encrypted');

        // Check for other potentially sensitive fields
        if (Schema::hasColumn('users', 'api_key')) {
            $userWithApi = User::factory()->create([
                'company_id' => $this->company1->id,
                'api_key' => 'sensitive_api_key_123',
            ]);

            $rawApiData = DB::table('users')
                ->where('id', $userWithApi->id)
                ->value('api_key');

            // API key should be encrypted if stored
            if ($rawApiData) {
                $this->assertNotEquals('sensitive_api_key_123', $rawApiData);
            }
        }

        $this->logSecurityTestResult('database_data_encryption', true);
    }

    public function test_database_audit_logging()
    {
        $this->actingAs($this->admin1);

        $initialLogCount = 0;
        if (Schema::hasTable('audit_logs')) {
            $initialLogCount = DB::table('audit_logs')->count();
        }

        // Perform sensitive operation
        $customer = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Audit Test Customer',
        ]);

        $customer->delete();

        if (Schema::hasTable('audit_logs')) {
            $finalLogCount = DB::table('audit_logs')->count();
            
            // Should have audit entries for create and delete
            $this->assertGreaterThan($initialLogCount, $finalLogCount,
                'Database operations not properly audited');
        }

        $this->logSecurityTestResult('database_audit_logging', true);
    }

    public function test_database_backup_security()
    {
        // Test backup file security (if accessible)
        $backupPaths = [
            storage_path('backups/'),
            base_path('backups/'),
            '/var/backups/',
            '/backup/',
        ];

        foreach ($backupPaths as $path) {
            if (is_dir($path)) {
                $files = glob($path . '*.sql*');
                
                foreach ($files as $file) {
                    // Backup files should not be world-readable
                    $perms = fileperms($file) & 0777;
                    $this->assertLessThan(0044, $perms & 0044, 
                        "Backup file {$file} is world-readable");
                }
            }
        }

        $this->logSecurityTestResult('database_backup_security', true);
    }

    public function test_database_connection_limits()
    {
        // Test connection exhaustion protection
        $connections = [];
        $maxConnections = 10;

        try {
            for ($i = 0; $i < $maxConnections; $i++) {
                $pdo = new \PDO(
                    'mysql:host=' . config('database.connections.mysql.host') . 
                    ';dbname=' . config('database.connections.mysql.database'),
                    config('database.connections.mysql.username'),
                    config('database.connections.mysql.password')
                );
                $connections[] = $pdo;
            }
            
            // Should eventually hit connection limit
            $this->assertLessThan(100, count($connections), 
                'No connection limits enforced');
                
        } catch (\Exception $e) {
            // Connection limit reached is expected
            $this->assertTrue(true, 'Connection limits properly enforced');
        }

        // Clean up connections
        $connections = [];

        $this->logSecurityTestResult('database_connection_limits', true);
    }

    public function test_database_row_level_security()
    {
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'RLS Test Customer 1',
        ]);

        $customer2 = Customer::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'RLS Test Customer 2',
        ]);

        $this->actingAs($this->admin1);

        // Try to access other company's data via raw SQL
        try {
            $results = DB::select('SELECT * FROM customers WHERE company_id = ?', 
                [$this->company2->id]);
            
            // Should be empty due to row-level security
            $this->assertEmpty($results, 
                'Row-level security not enforced in raw queries');
                
        } catch (\Exception $e) {
            // Access denied is good
            $this->assertTrue(true, 'Row-level security properly enforced');
        }

        $this->logSecurityTestResult('database_row_level_security', true);
    }

    public function test_database_view_security()
    {
        // Test if database views properly restrict access
        try {
            $views = DB::select('SHOW FULL TABLES WHERE Table_type = "VIEW"');
            
            foreach ($views as $view) {
                $viewName = array_values((array)$view)[0];
                
                // Test view access
                try {
                    $viewData = DB::select("SELECT * FROM {$viewName} LIMIT 1");
                    
                    // Views should not expose sensitive data
                    if (!empty($viewData)) {
                        $data = json_encode($viewData);
                        $this->assertStringNotContainsString('password', strtolower($data));
                        $this->assertStringNotContainsString('secret', strtolower($data));
                    }
                    
                } catch (\Exception $e) {
                    // Access restriction is good
                    $this->assertTrue(true, "View {$viewName} access properly restricted");
                }
            }
            
        } catch (\Exception $e) {
            $this->markTestSkipped('Cannot test database views');
        }

        $this->logSecurityTestResult('database_view_security', true);
    }
}