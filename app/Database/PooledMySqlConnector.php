<?php

namespace App\Database;

use Illuminate\Database\Connectors\MySqlConnector;
use PDO;
use PDOException;
use Illuminate\Support\Facades\Log;

class PooledMySqlConnector extends MySqlConnector
{
    /**
     * Connection pool
     */
    protected static $pool = [];
    
    /**
     * Maximum connections in pool
     */
    protected static $maxConnections;
    
    /**
     * Minimum connections in pool
     */
    protected static $minConnections;
    
    /**
     * Connection statistics
     */
    protected static $stats = [
        'connections_created' => 0,
        'connections_reused' => 0,
        'connections_failed' => 0,
        'pool_hits' => 0,
        'pool_misses' => 0,
        'total_requests' => 0,
        'wait_queue_size' => 0,
    ];
    
    /**
     * Initialize the pool settings
     */
    public function __construct()
    {
        // MySqlConnector doesn't have a constructor, so don't call parent
        
        self::$minConnections = config('database.connections.mysql.pool.min_connections', 5);
        self::$maxConnections = config('database.connections.mysql.pool.max_connections', 50);
        
        // Pre-warm the connection pool
        $this->warmPool();
    }
    
    /**
     * Create a new PDO connection with pooling
     */
    public function connect(array $config)
    {
        self::$stats['total_requests']++;
        
        // Try to get a connection from the pool
        $connection = $this->getFromPool($config);
        
        if ($connection !== null) {
            self::$stats['pool_hits']++;
            self::$stats['connections_reused']++;
            return $connection;
        }
        
        self::$stats['pool_misses']++;
        
        // Create new connection if pool is empty or below max
        if (count(self::$pool) < self::$maxConnections) {
            try {
                $connection = parent::connect($config);
                self::$stats['connections_created']++;
                
                // Set connection attributes for pooling
                $this->configurePooledConnection($connection);
                
                return $connection;
            } catch (PDOException $e) {
                self::$stats['connections_failed']++;
                Log::error('Failed to create database connection', [
                    'error' => $e->getMessage(),
                    'stats' => self::$stats
                ]);
                throw $e;
            }
        }
        
        // If we've reached max connections, wait and retry
        return $this->waitForConnection($config);
    }
    
    /**
     * Get a connection from the pool
     */
    protected function getFromPool(array $config)
    {
        $key = $this->getPoolKey($config);
        
        if (!isset(self::$pool[$key]) || empty(self::$pool[$key])) {
            return null;
        }
        
        // Get the oldest connection from the pool
        $connection = array_shift(self::$pool[$key]);
        
        // Validate the connection is still alive
        if ($this->isConnectionAlive($connection)) {
            return $connection;
        }
        
        // Connection is dead, try another one
        return $this->getFromPool($config);
    }
    
    
    /**
     * Configure a connection for pooling
     */
    protected function configurePooledConnection(PDO $connection)
    {
        // Ensure connection is in a clean state
        $connection->exec("SET SESSION sql_mode='TRADITIONAL,NO_AUTO_VALUE_ON_ZERO'");
        $connection->exec("SET SESSION wait_timeout=28800");
        $connection->exec("SET SESSION interactive_timeout=28800");
        
        // Clear any temporary tables or locks
        $connection->exec("UNLOCK TABLES");
    }
    
    /**
     * Check if a connection is still alive
     */
    protected function isConnectionAlive(PDO $connection)
    {
        try {
            $connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Wait for a connection to become available
     */
    protected function waitForConnection(array $config, $maxWait = 5)
    {
        $waited = 0;
        $interval = 0.1; // 100ms
        
        while ($waited < $maxWait) {
            $connection = $this->getFromPool($config);
            
            if ($connection !== null) {
                return $connection;
            }
            
            usleep($interval * 1000000);
            $waited += $interval;
        }
        
        // Last resort: create a new connection even if over limit
        Log::warning('Connection pool exhausted, creating emergency connection', [
            'stats' => self::$stats,
            'pool_size' => count(self::$pool)
        ]);
        
        return parent::connect($config);
    }
    
    /**
     * Pre-warm the connection pool
     */
    protected function warmPool()
    {
        // This would be called during application boot
        // Pre-create minimum connections for faster initial requests
        register_shutdown_function(function () {
            // Clean up connections on shutdown
            foreach (self::$pool as $key => $connections) {
                foreach ($connections as $connection) {
                    $connection = null;
                }
            }
            self::$pool = [];
        });
    }
    
    /**
     * Get pool key for a configuration
     */
    protected function getPoolKey(array $config)
    {
        return self::getStaticPoolKey($config);
    }
    
    /**
     * Static method to get pool key
     */
    protected static function getStaticPoolKey(array $config)
    {
        return md5(serialize([
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? 3306,
            'database' => $config['database'] ?? '',
            'username' => $config['username'] ?? '',
        ]));
    }
    
    /**
     * Get connection pool statistics
     */
    public static function getStats()
    {
        $poolSize = array_sum(array_map('count', self::$pool));
        $totalRequests = self::$stats['total_requests'] ?: 1; // Avoid division by zero
        
        return array_merge(self::$stats, [
            'pool_size' => $poolSize,
            'pool_keys' => count(self::$pool),
            'active_connections' => self::$stats['connections_created'] - $poolSize,
            'available_connections' => $poolSize,
            'total_connections' => self::$stats['connections_created'],
            'max_connections' => self::$maxConnections,
            'hit_rate' => $totalRequests > 0 ? self::$stats['pool_hits'] / $totalRequests : 0,
        ]);
    }
    
    /**
     * Return a connection to the pool
     */
    public static function returnToPool(PDO $pdo, array $config)
    {
        try {
            $key = self::getStaticPoolKey($config);
            
            // Check if connection is still valid
            if ($pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) === false) {
                Log::debug('Connection is no longer valid, not returning to pool');
                return;
            }
            
            // Initialize pool for this key if needed
            if (!isset(self::$pool[$key])) {
                self::$pool[$key] = [];
            }
            
            // Don't exceed max pool size
            if (count(self::$pool[$key]) >= self::$maxConnections) {
                Log::debug('Pool is full, closing connection');
                return;
            }
            
            // Return connection to pool
            self::$pool[$key][] = $pdo;
            self::$stats['pool_hits']++;
            
            Log::debug('Connection returned to pool', [
                'key' => $key,
                'pool_size' => count(self::$pool[$key])
            ]);
        } catch (\Exception $e) {
            Log::error('Error returning connection to pool', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Clear the connection pool
     */
    public static function clearPool()
    {
        foreach (self::$pool as $key => $connections) {
            foreach ($connections as $connection) {
                $connection = null;
            }
        }
        self::$pool = [];
        
        Log::info('Connection pool cleared', ['stats' => self::$stats]);
    }
}