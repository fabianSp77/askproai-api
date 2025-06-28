<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Connection;
use PDO;

/**
 * Database Connection Pool Manager
 * 
 * Manages database connections efficiently for high-concurrency scenarios
 * Supports read/write splitting and connection health monitoring
 */
class ConnectionPoolManager
{
    /**
     * @var self|null Singleton instance
     */
    private static ?self $instance = null;
    
    /**
     * @var array Connection pools
     */
    private static $pools = [
        'write' => [],
        'read' => []
    ];
    
    /**
     * @var array Pool configuration
     */
    private static $config = [
        'min_connections' => 10,
        'max_connections' => 100,
        'max_idle_time' => 300, // 5 minutes
        'health_check_interval' => 60, // 1 minute
        'connection_timeout' => 5,
        'retry_attempts' => 3,
        'retry_delay' => 100 // milliseconds
    ];
    
    /**
     * @var array Connection statistics
     */
    private static $stats = [
        'total_connections' => 0,
        'active_connections' => 0,
        'idle_connections' => 0,
        'failed_connections' => 0,
        'recycled_connections' => 0
    ];
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Set configuration
     */
    public function setConfig(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }
    
    /**
     * Get configuration
     */
    public function getConfig(): array
    {
        return self::$config;
    }
    
    /**
     * Initialize connection pools
     */
    public static function initialize()
    {
        // Create minimum connections for write pool
        for ($i = 0; $i < self::$config['min_connections']; $i++) {
            self::createConnection('write');
        }
        
        // Create minimum connections for read pool
        if (config('database.connections.mysql_read')) {
            for ($i = 0; $i < self::$config['min_connections']; $i++) {
                self::createConnection('read');
            }
        }
        
        // Start health check daemon
        self::startHealthCheckDaemon();
    }
    
    /**
     * Get a connection from the pool
     * 
     * @param string $type 'read' or 'write'
     * @return Connection
     */
    public static function getConnection($type = 'write')
    {
        $pool = &self::$pools[$type];
        
        // Find an idle connection
        foreach ($pool as $key => $conn) {
            if ($conn['status'] === 'idle' && self::isConnectionHealthy($conn['connection'])) {
                $pool[$key]['status'] = 'active';
                $pool[$key]['last_used'] = time();
                self::$stats['active_connections']++;
                self::$stats['idle_connections']--;
                return $conn['connection'];
            }
        }
        
        // No idle connections, create new one if under limit
        if (count($pool) < self::$config['max_connections']) {
            return self::createConnection($type);
        }
        
        // At max capacity, wait for a connection
        return self::waitForConnection($type);
    }
    
    /**
     * Release a connection back to the pool
     * 
     * @param Connection $connection
     * @param string $type
     */
    public static function releaseConnection($connection, $type = 'write')
    {
        $pool = &self::$pools[$type];
        
        foreach ($pool as $key => $conn) {
            if ($conn['connection'] === $connection) {
                // Check if connection is still healthy
                if (self::isConnectionHealthy($connection)) {
                    $pool[$key]['status'] = 'idle';
                    $pool[$key]['last_used'] = time();
                    self::$stats['active_connections']--;
                    self::$stats['idle_connections']++;
                } else {
                    // Remove unhealthy connection
                    unset($pool[$key]);
                    self::$stats['active_connections']--;
                    self::$stats['total_connections']--;
                    
                    // Create replacement connection
                    self::createConnection($type);
                }
                break;
            }
        }
    }
    
    /**
     * Create a new connection
     * 
     * @param string $type
     * @return Connection
     */
    private static function createConnection($type)
    {
        $attempts = 0;
        $connection = null;
        
        while ($attempts < self::$config['retry_attempts']) {
            try {
                // Default to 'mysql' if 'mysql_read' doesn't exist
                $configKey = ($type === 'read' && config('database.connections.mysql_read')) 
                    ? 'mysql_read' 
                    : 'mysql';
                $config = config('database.connections.' . $configKey);
                
                if (!$config) {
                    throw new \Exception("Database configuration not found for: " . $configKey);
                }
                
                // Create PDO with connection pooling options
                $pdo = new PDO(
                    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
                        $config['host'],
                        $config['port'],
                        $config['database'],
                        $config['charset']
                    ),
                    $config['username'],
                    $config['password'],
                    [
                        PDO::ATTR_PERSISTENT => true,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => self::$config['connection_timeout'],
                        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'"
                    ]
                );
                
                // Create Laravel connection wrapper
                $connection = new \Illuminate\Database\MySqlConnection($pdo, $config['database'], $config['prefix'], $config);
                
                // Add to pool
                self::$pools[$type][] = [
                    'connection' => $connection,
                    'status' => 'active',
                    'created_at' => time(),
                    'last_used' => time(),
                    'health_checked_at' => time()
                ];
                
                self::$stats['total_connections']++;
                self::$stats['active_connections']++;
                
                return $connection;
                
            } catch (\Exception $e) {
                $attempts++;
                self::$stats['failed_connections']++;
                
                if ($attempts >= self::$config['retry_attempts']) {
                    Log::error('Failed to create database connection', [
                        'type' => $type,
                        'attempts' => $attempts,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
                
                usleep(self::$config['retry_delay'] * 1000);
            }
        }
    }
    
    /**
     * Wait for an available connection
     * 
     * @param string $type
     * @return Connection
     */
    private static function waitForConnection($type)
    {
        $maxWaitTime = 30; // 30 seconds
        $waitInterval = 100000; // 100ms
        $waited = 0;
        
        while ($waited < $maxWaitTime * 1000000) {
            $pool = &self::$pools[$type];
            
            foreach ($pool as $key => $conn) {
                if ($conn['status'] === 'idle') {
                    $pool[$key]['status'] = 'active';
                    $pool[$key]['last_used'] = time();
                    self::$stats['active_connections']++;
                    self::$stats['idle_connections']--;
                    return $conn['connection'];
                }
            }
            
            usleep($waitInterval);
            $waited += $waitInterval;
        }
        
        throw new \Exception('Connection pool timeout - no connections available');
    }
    
    /**
     * Check if a connection is healthy
     * 
     * @param Connection $connection
     * @return bool
     */
    private static function isConnectionHealthy($connection)
    {
        try {
            $pdo = $connection->getPdo();
            $stmt = $pdo->query('SELECT 1');
            $stmt->closeCursor();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Start health check daemon
     */
    private static function startHealthCheckDaemon()
    {
        // In production, this would be a separate process
        // For now, we'll check on each request
        register_shutdown_function(function () {
            self::performHealthCheck();
        });
    }
    
    /**
     * Perform health check on all connections
     */
    private static function performHealthCheck()
    {
        $now = time();
        
        foreach (['write', 'read'] as $type) {
            $pool = &self::$pools[$type];
            
            foreach ($pool as $key => $conn) {
                // Remove idle connections that exceeded max idle time
                if ($conn['status'] === 'idle' && 
                    ($now - $conn['last_used']) > self::$config['max_idle_time']) {
                    unset($pool[$key]);
                    self::$stats['idle_connections']--;
                    self::$stats['total_connections']--;
                    self::$stats['recycled_connections']++;
                    continue;
                }
                
                // Health check active connections
                if (($now - $conn['health_checked_at']) > self::$config['health_check_interval']) {
                    if (!self::isConnectionHealthy($conn['connection'])) {
                        unset($pool[$key]);
                        self::$stats['total_connections']--;
                        if ($conn['status'] === 'active') {
                            self::$stats['active_connections']--;
                        } else {
                            self::$stats['idle_connections']--;
                        }
                    } else {
                        $pool[$key]['health_checked_at'] = $now;
                    }
                }
            }
            
            // Ensure minimum connections
            $currentCount = count($pool);
            if ($currentCount < self::$config['min_connections']) {
                for ($i = $currentCount; $i < self::$config['min_connections']; $i++) {
                    self::createConnection($type);
                }
            }
        }
    }
    
    /**
     * Clean up all connections
     */
    public function cleanup(): void
    {
        foreach (['write', 'read'] as $type) {
            foreach (self::$pools[$type] as $conn) {
                try {
                    $conn['connection']->disconnect();
                } catch (\Exception $e) {
                    // Ignore errors during cleanup
                }
            }
            self::$pools[$type] = [];
        }
        
        self::$stats = [
            'total_connections' => 0,
            'active_connections' => 0,
            'idle_connections' => 0,
            'failed_connections' => 0,
            'recycled_connections' => 0
        ];
        
        // Don't log during shutdown as the app container might be gone
        if (app()->bound('log')) {
            try {
                Log::info('Database connection pool cleaned up');
            } catch (\Exception $e) {
                // Ignore logging errors during cleanup
            }
        }
    }
    
    /**
     * Get pool statistics
     * 
     * @return array
     */
    public static function getStats()
    {
        return array_merge(self::$stats, [
            'write_pool_size' => count(self::$pools['write']),
            'read_pool_size' => count(self::$pools['read']),
            'config' => self::$config
        ]);
    }
    
    /**
     * Configure pool settings
     * 
     * @param array $config
     */
    public static function configure(array $config)
    {
        self::$config = array_merge(self::$config, $config);
    }
    
    /**
     * Gracefully shutdown all connections
     */
    public static function shutdown()
    {
        foreach (['write', 'read'] as $type) {
            foreach (self::$pools[$type] as $conn) {
                try {
                    $conn['connection']->disconnect();
                } catch (\Exception $e) {
                    // Ignore errors during shutdown
                }
            }
            self::$pools[$type] = [];
        }
        
        self::$stats = [
            'total_connections' => 0,
            'active_connections' => 0,
            'idle_connections' => 0,
            'failed_connections' => 0,
            'recycled_connections' => 0
        ];
    }
}