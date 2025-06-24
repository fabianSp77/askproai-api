# Database Configuration

## Overview

AskProAI uses MySQL/MariaDB as the primary database with support for read replicas, connection pooling, and performance optimization. This guide covers database configuration, optimization, and best practices.

## Database Connection

### Primary Configuration
```php
// config/database.php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'askproai_db'),
            'username' => env('DB_USERNAME', 'askproai_user'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::MYSQL_ATTR_SSL_CERT => env('MYSQL_ATTR_SSL_CERT'),
                PDO::MYSQL_ATTR_SSL_KEY => env('MYSQL_ATTR_SSL_KEY'),
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                PDO::ATTR_PERSISTENT => env('DB_PERSISTENT', false),
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
            ]) : [],
        ],
    ],
];
```

### Read/Write Splitting
```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => [
            env('DB_READ_HOST_1', '127.0.0.1'),
            env('DB_READ_HOST_2', '127.0.0.1'),
        ],
        'port' => env('DB_READ_PORT', 3306),
        'username' => env('DB_READ_USERNAME', 'askproai_readonly'),
        'password' => env('DB_READ_PASSWORD'),
    ],
    'write' => [
        'host' => [
            env('DB_WRITE_HOST', '127.0.0.1'),
        ],
        'port' => env('DB_WRITE_PORT', 3306),
        'username' => env('DB_WRITE_USERNAME', 'askproai_user'),
        'password' => env('DB_WRITE_PASSWORD'),
    ],
    'sticky' => env('DB_STICKY', true), // Keep reading from write after writes
    'driver' => 'mysql',
    'database' => env('DB_DATABASE', 'askproai_db'),
    // ... other settings
],
```

## Connection Pooling

### Database Connection Pool
```php
// app/Services/Database/ConnectionPool.php
class ConnectionPool
{
    private array $connections = [];
    private int $maxConnections;
    private int $minConnections;
    
    public function __construct()
    {
        $this->maxConnections = config('database.pool.max', 20);
        $this->minConnections = config('database.pool.min', 5);
        
        $this->initializePool();
    }
    
    private function initializePool(): void
    {
        for ($i = 0; $i < $this->minConnections; $i++) {
            $this->connections[] = $this->createConnection();
        }
    }
    
    public function getConnection(): PDO
    {
        if (empty($this->connections) && count($this->activeConnections) < $this->maxConnections) {
            return $this->createConnection();
        }
        
        return array_pop($this->connections) ?? $this->waitForConnection();
    }
    
    public function releaseConnection(PDO $connection): void
    {
        if ($this->isHealthy($connection)) {
            $this->connections[] = $connection;
        }
    }
    
    private function isHealthy(PDO $connection): bool
    {
        try {
            $connection->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
```

### Connection Management
```php
// config/database.php
'pool' => [
    'min' => env('DB_POOL_MIN', 2),
    'max' => env('DB_POOL_MAX', 10),
    'idle_timeout' => env('DB_POOL_IDLE_TIMEOUT', 60),
    'max_lifetime' => env('DB_POOL_MAX_LIFETIME', 3600),
    'wait_timeout' => env('DB_POOL_WAIT_TIMEOUT', 3),
],
```

## Database Optimization

### Index Configuration
```php
// database/migrations/2025_06_23_add_performance_indexes.php
class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        // Composite indexes for common queries
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['company_id', 'date', 'status'], 'idx_company_date_status');
            $table->index(['branch_id', 'date'], 'idx_branch_date');
            $table->index(['customer_id', 'created_at'], 'idx_customer_created');
        });
        
        // Covering indexes for read-heavy queries
        Schema::table('calls', function (Blueprint $table) {
            $table->index(['company_id', 'created_at', 'status'], 'idx_company_created_status');
            $table->index(['phone_number', 'created_at'], 'idx_phone_created');
        });
        
        // Full-text search indexes
        DB::statement('ALTER TABLE customers ADD FULLTEXT idx_fulltext_search (name, email, notes)');
        
        // Partial indexes for filtered queries
        DB::statement('CREATE INDEX idx_active_appointments ON appointments (date, time) WHERE status = "scheduled"');
    }
}
```

### Query Optimization
```php
// app/Services/Database/QueryOptimizer.php
class QueryOptimizer
{
    public function analyzeSlowQueries(): array
    {
        $slowQueries = DB::select("
            SELECT 
                query_time,
                lock_time,
                rows_sent,
                rows_examined,
                sql_text,
                digest
            FROM mysql.slow_log
            WHERE query_time > 1
            ORDER BY query_time DESC
            LIMIT 20
        ");
        
        return collect($slowQueries)->map(function ($query) {
            return [
                'query' => $query->sql_text,
                'time' => $query->query_time,
                'rows_examined' => $query->rows_examined,
                'efficiency' => $query->rows_sent / max($query->rows_examined, 1),
                'suggestions' => $this->getSuggestions($query),
            ];
        })->toArray();
    }
    
    public function explainQuery(string $sql): array
    {
        $explanation = DB::select("EXPLAIN {$sql}");
        
        return collect($explanation)->map(function ($row) {
            return [
                'table' => $row->table,
                'type' => $row->type,
                'possible_keys' => $row->possible_keys,
                'key' => $row->key,
                'rows' => $row->rows,
                'extra' => $row->Extra,
                'optimization_level' => $this->rateOptimization($row),
            ];
        })->toArray();
    }
    
    private function rateOptimization($explanation): string
    {
        if ($explanation->type === 'ALL') {
            return 'poor'; // Full table scan
        }
        
        if (in_array($explanation->type, ['const', 'eq_ref'])) {
            return 'excellent';
        }
        
        if (in_array($explanation->type, ['ref', 'range'])) {
            return 'good';
        }
        
        return 'fair';
    }
}
```

## Migration Strategies

### Zero-Downtime Migrations
```php
// app/Console/Commands/MigrateOnline.php
class MigrateOnline extends Command
{
    protected $signature = 'migrate:online {migration}';
    
    public function handle()
    {
        $migration = $this->argument('migration');
        
        // Use pt-online-schema-change for large tables
        if ($this->isLargeTable($migration)) {
            $this->runOnlineSchemaChange($migration);
        } else {
            $this->runStandardMigration($migration);
        }
    }
    
    private function runOnlineSchemaChange($migration)
    {
        $alterStatement = $this->getAlterStatement($migration);
        $table = $this->getTableName($migration);
        
        $command = sprintf(
            'pt-online-schema-change --alter="%s" D=%s,t=%s --execute',
            $alterStatement,
            config('database.connections.mysql.database'),
            $table
        );
        
        $this->info("Running online schema change: {$command}");
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->error('Online schema change failed');
            return 1;
        }
        
        $this->info('Online schema change completed successfully');
    }
}
```

### Migration Best Practices
```php
// database/migrations/2025_06_23_example_migration.php
class ExampleMigration extends Migration
{
    public function up()
    {
        // Disable foreign key checks for performance
        Schema::disableForeignKeyConstraints();
        
        // Use raw SQL for better control
        DB::statement('SET SESSION innodb_lock_wait_timeout = 5');
        
        // Add columns with default values to avoid locking
        Schema::table('large_table', function (Blueprint $table) {
            $table->boolean('new_column')->default(false)->after('existing_column');
        });
        
        // Create indexes concurrently (MySQL 8.0+)
        DB::statement('CREATE INDEX CONCURRENTLY idx_new ON large_table(new_column)');
        
        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();
    }
    
    public function down()
    {
        // Always provide rollback capability
        Schema::table('large_table', function (Blueprint $table) {
            $table->dropColumn('new_column');
        });
    }
}
```

## Database Monitoring

### Performance Metrics
```php
// app/Services/Database/DatabaseMonitor.php
class DatabaseMonitor
{
    public function getMetrics(): array
    {
        return [
            'connections' => $this->getConnectionMetrics(),
            'performance' => $this->getPerformanceMetrics(),
            'storage' => $this->getStorageMetrics(),
            'replication' => $this->getReplicationMetrics(),
        ];
    }
    
    private function getConnectionMetrics(): array
    {
        $result = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0];
        $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'")[0];
        
        return [
            'current' => $result->Value,
            'max' => $maxConnections->Value,
            'usage_percentage' => round(($result->Value / $maxConnections->Value) * 100, 2),
        ];
    }
    
    private function getPerformanceMetrics(): array
    {
        $status = collect(DB::select("SHOW GLOBAL STATUS"))
            ->pluck('Value', 'Variable_name');
        
        return [
            'queries_per_second' => round($status['Questions'] / $status['Uptime'], 2),
            'slow_queries' => $status['Slow_queries'],
            'table_locks_waited' => $status['Table_locks_waited'],
            'innodb_buffer_pool_hit_rate' => $this->calculateBufferPoolHitRate($status),
        ];
    }
    
    private function calculateBufferPoolHitRate($status): float
    {
        $reads = $status['Innodb_buffer_pool_reads'] ?? 0;
        $readRequests = $status['Innodb_buffer_pool_read_requests'] ?? 1;
        
        return round((1 - ($reads / $readRequests)) * 100, 2);
    }
}
```

### Query Logging
```php
// app/Providers/DatabaseQueryLogProvider.php
class DatabaseQueryLogProvider extends ServiceProvider
{
    public function boot()
    {
        if (config('app.debug') || config('database.log_queries')) {
            DB::listen(function ($query) {
                Log::channel('queries')->info('Query executed', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'connection' => $query->connectionName,
                ]);
                
                // Alert on slow queries
                if ($query->time > 1000) { // Over 1 second
                    Log::channel('slow-queries')->warning('Slow query detected', [
                        'sql' => $query->sql,
                        'time' => $query->time,
                        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
                    ]);
                }
            });
        }
    }
}
```

## Backup Configuration

### Automated Backups
```php
// config/backup.php
return [
    'backup' => [
        'name' => env('APP_NAME', 'askproai'),
        'source' => [
            'databases' => ['mysql'],
        ],
        'database_dump_compressor' => 'gzip',
        'database_dump_file_extension' => 'sql.gz',
        'destination' => [
            'filename_prefix' => 'backup-',
            'disks' => ['local', 's3'],
        ],
        'temporary_directory' => storage_path('app/backup-temp'),
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'default',
    ],
    
    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class,
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class,
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class,
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class,
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class,
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class,
        ],
        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,
        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL', 'admin@askproai.de'),
        ],
    ],
    
    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 30,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 12,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
    ],
];
```

### Backup Verification
```php
// app/Console/Commands/VerifyBackup.php
class VerifyBackup extends Command
{
    protected $signature = 'backup:verify {file}';
    
    public function handle()
    {
        $file = $this->argument('file');
        
        // Extract backup
        $this->info('Extracting backup...');
        $extractPath = $this->extractBackup($file);
        
        // Verify database dump
        $this->info('Verifying database dump...');
        if (!$this->verifyDatabaseDump($extractPath)) {
            $this->error('Database dump verification failed');
            return 1;
        }
        
        // Test restore to temporary database
        $this->info('Testing restore...');
        if (!$this->testRestore($extractPath)) {
            $this->error('Restore test failed');
            return 1;
        }
        
        $this->info('Backup verification successful');
        return 0;
    }
}
```

## Database Security

### Encryption at Rest
```php
// config/database.php
'mysql' => [
    // ... other config
    'options' => [
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::MYSQL_ATTR_SSL_CERT => env('MYSQL_ATTR_SSL_CERT'),
        PDO::MYSQL_ATTR_SSL_KEY => env('MYSQL_ATTR_SSL_KEY'),
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
    ],
],

// Enable table encryption
DB::statement("ALTER TABLE sensitive_data ENCRYPTION='Y'");
```

### Access Control
```sql
-- Create read-only user for reporting
CREATE USER 'askproai_readonly'@'%' IDENTIFIED BY 'secure_password';
GRANT SELECT ON askproai_db.* TO 'askproai_readonly'@'%';

-- Create backup user with minimal privileges
CREATE USER 'askproai_backup'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT, LOCK TABLES, SHOW VIEW, EVENT, TRIGGER ON askproai_db.* TO 'askproai_backup'@'localhost';

-- Application user with specific privileges
CREATE USER 'askproai_app'@'%' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE TEMPORARY TABLES ON askproai_db.* TO 'askproai_app'@'%';
```

## Database Maintenance

### Optimization Schedule
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Optimize tables weekly
    $schedule->command('db:optimize')
        ->weeklyOn(0, '03:00')
        ->withoutOverlapping();
    
    // Analyze tables for query optimizer
    $schedule->command('db:analyze')
        ->daily()
        ->at('04:00');
    
    // Clean up old data
    $schedule->command('db:cleanup')
        ->daily()
        ->at('02:00');
    
    // Update statistics
    $schedule->command('db:update-stats')
        ->twiceDaily(6, 18);
}
```

### Maintenance Commands
```php
// app/Console/Commands/OptimizeDatabase.php
class OptimizeDatabase extends Command
{
    protected $signature = 'db:optimize {--table=}';
    
    public function handle()
    {
        $tables = $this->option('table') 
            ? [$this->option('table')] 
            : $this->getAllTables();
        
        foreach ($tables as $table) {
            $this->info("Optimizing table: {$table}");
            
            DB::statement("ANALYZE TABLE {$table}");
            DB::statement("OPTIMIZE TABLE {$table}");
            
            // Update index statistics
            DB::statement("ALTER TABLE {$table} ENGINE=InnoDB");
        }
        
        $this->info('Database optimization completed');
    }
}
```

## Testing Configuration

### Test Database
```php
// config/database.php
'connections' => [
    'mysql_testing' => [
        'driver' => 'mysql',
        'host' => env('DB_TEST_HOST', '127.0.0.1'),
        'port' => env('DB_TEST_PORT', '3306'),
        'database' => env('DB_TEST_DATABASE', 'askproai_test'),
        'username' => env('DB_TEST_USERNAME', 'root'),
        'password' => env('DB_TEST_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => 'InnoDB',
    ],
    
    'sqlite_testing' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ],
],
```

## Related Documentation
- [Migration Guide](../deployment/migration.md)
- [Performance Optimization](../operations/performance.md)
- [Backup Strategies](../deployment/backup.md)
- [Security Configuration](security.md)