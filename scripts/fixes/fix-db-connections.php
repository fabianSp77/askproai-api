#!/usr/bin/env php
<?php
/**
 * Fix Database Connection Limit Issues
 * 
 * This script fixes "Too many connections" errors by:
 * 1. Checking current connections
 * 2. Killing idle connections
 * 3. Optimizing connection settings
 * 
 * Error Code: DB_002
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 Database Connection Fix Script\n";
echo "=================================\n\n";

try {
    // Step 1: Check current connection status
    echo "1. Checking current database connections...\n";
    
    try {
        // Get current connection info
        $variables = DB::select("SHOW VARIABLES LIKE '%max_connections%'");
        $maxConnections = 0;
        foreach ($variables as $var) {
            if ($var->Variable_name === 'max_connections') {
                $maxConnections = $var->Value;
                echo "   Max connections allowed: {$maxConnections}\n";
            }
        }
        
        // Get current connection count
        $status = DB::select("SHOW STATUS LIKE 'Threads_connected'");
        $currentConnections = 0;
        foreach ($status as $stat) {
            if ($stat->Variable_name === 'Threads_connected') {
                $currentConnections = $stat->Value;
                echo "   Current connections: {$currentConnections}\n";
            }
        }
        
        $percentage = $maxConnections > 0 ? round(($currentConnections / $maxConnections) * 100, 2) : 0;
        echo "   Connection usage: {$percentage}%\n";
        
        if ($percentage > 80) {
            echo "   ⚠️  Connection usage is high!\n";
        }
        
    } catch (\Exception $e) {
        echo "   ❌ Could not get connection info: " . $e->getMessage() . "\n";
    }

    // Step 2: Show active connections
    echo "\n2. Analyzing active connections...\n";
    
    $processes = DB::select("SHOW PROCESSLIST");
    $connectionsByUser = [];
    $connectionsByHost = [];
    $idleConnections = [];
    
    foreach ($processes as $process) {
        // Group by user
        $user = $process->User ?? 'unknown';
        if (!isset($connectionsByUser[$user])) {
            $connectionsByUser[$user] = 0;
        }
        $connectionsByUser[$user]++;
        
        // Group by host
        $host = explode(':', $process->Host ?? 'unknown')[0];
        if (!isset($connectionsByHost[$host])) {
            $connectionsByHost[$host] = 0;
        }
        $connectionsByHost[$host]++;
        
        // Check for idle connections
        if (($process->Command ?? '') === 'Sleep' && ($process->Time ?? 0) > 300) {
            $idleConnections[] = [
                'id' => $process->Id,
                'user' => $process->User,
                'time' => $process->Time,
                'host' => $process->Host,
            ];
        }
    }
    
    echo "   Connections by user:\n";
    foreach ($connectionsByUser as $user => $count) {
        echo "   - {$user}: {$count}\n";
    }
    
    echo "\n   Connections by host:\n";
    foreach ($connectionsByHost as $host => $count) {
        echo "   - {$host}: {$count}\n";
    }
    
    echo "\n   Idle connections (>5 minutes): " . count($idleConnections) . "\n";

    // Step 3: Kill idle connections if needed
    if (count($idleConnections) > 0) {
        echo "\n3. Found " . count($idleConnections) . " idle connections\n";
        
        // Show first 5 idle connections
        $showCount = min(5, count($idleConnections));
        for ($i = 0; $i < $showCount; $i++) {
            $conn = $idleConnections[$i];
            echo "   - ID: {$conn['id']}, User: {$conn['user']}, Idle: " . round($conn['time'] / 60) . " minutes\n";
        }
        
        if (count($idleConnections) > 10) {
            echo "\n   Kill all idle connections? (yes/no): ";
            $handle = fopen("php://stdin", "r");
            $line = trim(fgets($handle));
            fclose($handle);
            
            if ($line === 'yes') {
                $killed = 0;
                foreach ($idleConnections as $conn) {
                    try {
                        DB::statement("KILL {$conn['id']}");
                        $killed++;
                    } catch (\Exception $e) {
                        // Connection might have closed already
                    }
                }
                echo "   ✅ Killed {$killed} idle connections\n";
            }
        }
    }

    // Step 4: Check Laravel database configuration
    echo "\n4. Checking Laravel database configuration...\n";
    
    $dbConfig = config('database.connections.mysql');
    echo "   Database: " . ($dbConfig['database'] ?? 'N/A') . "\n";
    echo "   Host: " . ($dbConfig['host'] ?? 'N/A') . "\n";
    echo "   Persistent connections: " . (($dbConfig['options'][PDO::ATTR_PERSISTENT] ?? false) ? 'Yes' : 'No') . "\n";
    
    // Step 5: Provide optimization recommendations
    echo "\n5. Optimization recommendations...\n";
    
    echo "   a) Increase max_connections in MySQL:\n";
    echo "      Edit /etc/mysql/my.cnf or /etc/my.cnf:\n";
    echo "      ```\n";
    echo "      [mysqld]\n";
    echo "      max_connections = 500\n";
    echo "      ```\n";
    echo "      Then restart MySQL: sudo systemctl restart mysql\n";
    
    echo "\n   b) Enable connection pooling in Laravel:\n";
    echo "      In config/database.php:\n";
    echo "      ```php\n";
    echo "      'mysql' => [\n";
    echo "          // ... other config\n";
    echo "          'options' => [\n";
    echo "              PDO::ATTR_PERSISTENT => true,\n";
    echo "          ],\n";
    echo "      ],\n";
    echo "      ```\n";
    
    echo "\n   c) Use read/write splitting:\n";
    echo "      Configure read replicas for SELECT queries\n";
    
    echo "\n   d) Implement connection limits per user:\n";
    echo "      ```sql\n";
    echo "      ALTER USER 'askproai_user'@'localhost' WITH MAX_USER_CONNECTIONS 100;\n";
    echo "      ```\n";

    // Step 6: Test new connection
    echo "\n6. Testing new database connection...\n";
    
    try {
        $testConnection = DB::connection()->getPdo();
        echo "   ✅ Successfully created new connection\n";
    } catch (\Exception $e) {
        echo "   ❌ Failed to create new connection: " . $e->getMessage() . "\n";
        echo "   The connection limit may be reached\n";
    }

    // Summary
    echo "\n✅ Database connection analysis completed!\n";
    echo "\nCurrent status:\n";
    echo "- Connections: {$currentConnections}/{$maxConnections} ({$percentage}%)\n";
    echo "- Idle connections: " . count($idleConnections) . "\n";
    echo "- Laravel app connections: " . count($connectionsByUser['askproai_user'] ?? []) . "\n";
    
    if ($percentage > 80) {
        echo "\n⚠️  Action required:\n";
        echo "1. Increase max_connections in MySQL configuration\n";
        echo "2. Restart MySQL service\n";
        echo "3. Enable connection pooling in Laravel\n";
        echo "4. Consider using read replicas\n";
    }
    
    exit(0);

} catch (\Exception $e) {
    echo "\n❌ Error during fix process: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}