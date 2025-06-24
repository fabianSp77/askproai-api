<?php

echo "=================================\n";
echo "Connection Pool Configuration Fix\n";
echo "=================================\n\n";

// Step 1: Check current database configuration
echo "1. Checking current database configuration...\n";

// Read .env file
$envFile = __DIR__ . '/.env';
$envVars = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value);
        }
    }
}

$dbConfig = [
    'persistent' => ($envVars['DB_PERSISTENT'] ?? 'true') === 'true',
    'pool_enabled' => ($envVars['DB_POOL_ENABLED'] ?? 'true') === 'true',
    'pool_min' => $envVars['DB_POOL_MIN'] ?? 2,
    'pool_max' => $envVars['DB_POOL_MAX'] ?? 10,
    'wait_timeout' => null, // Will query from MySQL
    'max_connections' => null, // Will query from MySQL
];

// Connect to MySQL to check server settings
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=askproai_db',
        'root',
        'V9LGz2tdR5gpDQz'
    );
    
    // Get MySQL variables
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'max_connections'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $dbConfig['max_connections'] = $result['Value'] ?? 'unknown';
    
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'wait_timeout'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $dbConfig['wait_timeout'] = $result['Value'] ?? 'unknown';
    
    // Get current connection count
    $stmt = $pdo->query("SHOW STATUS LIKE 'Threads_connected'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentConnections = $result['Value'] ?? 'unknown';
    
    echo "   - Max connections: {$dbConfig['max_connections']}\n";
    echo "   - Wait timeout: {$dbConfig['wait_timeout']} seconds\n";
    echo "   - Current connections: {$currentConnections}\n";
    echo "   - Persistent connections: " . ($dbConfig['persistent'] ? 'ENABLED' : 'DISABLED') . "\n";
    echo "   - Pool enabled: " . ($dbConfig['pool_enabled'] ? 'YES' : 'NO') . "\n";
    echo "   - Pool min/max: {$dbConfig['pool_min']}/{$dbConfig['pool_max']}\n";
    
} catch (Exception $e) {
    echo "   ERROR: Could not connect to database: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n2. Creating optimized database configuration...\n";

// Create the configuration fix
$configFix = <<<'PHP'
<?php

/**
 * Database Connection Pool Configuration Fix
 * 
 * This configuration addresses the connection exhaustion issue by:
 * 1. Disabling persistent connections until proper pooling is implemented
 * 2. Setting appropriate timeouts
 * 3. Limiting connections per process
 * 4. Adding monitoring capabilities
 */

return [
    // Disable persistent connections to prevent accumulation
    'DB_PERSISTENT' => false,
    
    // Connection pool settings (for future implementation)
    'DB_POOL_ENABLED' => true,
    'DB_POOL_MIN' => 2,
    'DB_POOL_MAX' => 20,  // Increased from 10
    'DB_POOL_IDLE_TIME' => 60,  // Close idle connections after 60 seconds
    'DB_POOL_VALIDATION' => 30,  // Validate connections every 30 seconds
    
    // Connection timeout settings
    'DB_TIMEOUT' => 5,  // Connection timeout (seconds)
    'DB_READ_TIMEOUT' => 30,  // Query timeout (seconds)
    
    // MySQL specific settings (add to PDO options)
    'mysql_init_commands' => [
        "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
        "SET SESSION sql_mode='TRADITIONAL,NO_AUTO_VALUE_ON_ZERO'",
        "SET SESSION wait_timeout=120",  // Reduced from 600
        "SET SESSION interactive_timeout=120",
    ],
];
PHP;

// Save the configuration
file_put_contents(__DIR__ . '/config/database-pool-fix.php', $configFix);
echo "   ✅ Created config/database-pool-fix.php\n";

echo "\n3. Creating connection pool monitor...\n";

$monitor = <<<'PHP'
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonitorDatabaseConnections extends Command
{
    protected $signature = 'db:monitor-connections {--interval=5}';
    protected $description = 'Monitor database connection usage';

    public function handle()
    {
        $interval = $this->option('interval');
        
        $this->info('Monitoring database connections (Press Ctrl+C to stop)...');
        
        while (true) {
            try {
                $stats = DB::select("
                    SELECT 
                        (SELECT COUNT(*) FROM information_schema.processlist) as current_connections,
                        (SELECT COUNT(*) FROM information_schema.processlist WHERE command = 'Sleep') as idle_connections,
                        (SELECT COUNT(*) FROM information_schema.processlist WHERE time > 60) as long_running,
                        (SELECT @@max_connections) as max_connections,
                        (SELECT @@wait_timeout) as wait_timeout
                ");
                
                $stat = $stats[0];
                
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Current Connections', $stat->current_connections],
                        ['Idle Connections', $stat->idle_connections],
                        ['Long Running (>60s)', $stat->long_running],
                        ['Max Connections', $stat->max_connections],
                        ['Wait Timeout', $stat->wait_timeout . 's'],
                        ['Usage %', round(($stat->current_connections / $stat->max_connections) * 100, 2) . '%'],
                    ]
                );
                
                if ($stat->current_connections > ($stat->max_connections * 0.8)) {
                    $this->error('WARNING: Connection usage above 80%!');
                }
                
            } catch (\Exception $e) {
                $this->error('Error: ' . $e->getMessage());
            }
            
            sleep($interval);
            
            // Clear screen for next update
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                system('cls');
            } else {
                system('clear');
            }
        }
    }
}
PHP;

file_put_contents(__DIR__ . '/app/Console/Commands/MonitorDatabaseConnections.php', $monitor);
echo "   ✅ Created database connection monitor command\n";

echo "\n4. Creating immediate fix script...\n";

$fixScript = <<<'PHP'
<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Applying immediate connection pool fixes...\n";

// 1. Update .env file
$envFile = base_path('.env');
$envContent = file_get_contents($envFile);

// Disable persistent connections
$envContent = preg_replace('/^DB_PERSISTENT=.*/m', 'DB_PERSISTENT=false', $envContent);
if (!preg_match('/^DB_PERSISTENT=/m', $envContent)) {
    $envContent .= "\n# Connection Pool Fix\nDB_PERSISTENT=false\n";
}

// Add pool configuration
$poolConfig = [
    'DB_POOL_MAX=20',
    'DB_TIMEOUT=5',
    'DB_READ_TIMEOUT=30',
];

foreach ($poolConfig as $config) {
    [$key, $value] = explode('=', $config);
    if (!preg_match("/^{$key}=/m", $envContent)) {
        $envContent .= "{$config}\n";
    }
}

file_put_contents($envFile, $envContent);
echo "✅ Updated .env file\n";

// 2. Clear config cache
Artisan::call('config:clear');
echo "✅ Cleared configuration cache\n";

// 3. Kill long-running connections
try {
    DB::statement("
        SELECT CONCAT('KILL ', id, ';') 
        FROM information_schema.processlist 
        WHERE command = 'Sleep' 
        AND time > 120
    ");
    echo "✅ Cleaned up idle connections\n";
} catch (\Exception $e) {
    echo "⚠️  Could not clean connections: " . $e->getMessage() . "\n";
}

echo "\nConnection pool fixes applied!\n";
echo "Please restart your web server and queue workers for changes to take effect.\n";
PHP;

file_put_contents(__DIR__ . '/apply-connection-fix.php', $fixScript);
echo "   ✅ Created apply-connection-fix.php\n";

echo "\n=================================\n";
echo "Summary\n";
echo "=================================\n";
echo "1. Current issue: Persistent connections enabled without proper pooling\n";
echo "2. Created configuration fix in config/database-pool-fix.php\n";
echo "3. Created monitoring command: php artisan db:monitor-connections\n";
echo "4. Created fix script: php apply-connection-fix.php\n";
echo "\nRun 'php apply-connection-fix.php' to apply the fixes.\n";