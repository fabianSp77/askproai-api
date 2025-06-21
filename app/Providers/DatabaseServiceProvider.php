<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Database\ConnectionPoolManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register connection pool manager as singleton
        $this->app->singleton(ConnectionPoolManager::class, function ($app) {
            return ConnectionPoolManager::getInstance();
        });
    }
    
    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Register health check route
        $this->registerHealthCheckRoute();
    }
    
    /**
     * Enable connection pooling
     */
    private function enableConnectionPooling(): void
    {
        // Override default database connector
        DB::extend('mysql', function ($config, $name) {
            $config['name'] = $name;
            
            // Add pool configuration
            $config['pool'] = array_merge(
                config('database.pool', []),
                $config['pool'] ?? []
            );
            
            // Enable persistent connections
            $config['options'] = array_merge(
                $config['options'] ?? [],
                [
                    \PDO::ATTR_PERSISTENT => true,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
                ]
            );
            
            return new \Illuminate\Database\MySqlConnection(
                new \PDO(
                    $this->getDsn($config),
                    $config['username'],
                    $config['password'],
                    $config['options']
                ),
                $config['database'],
                $config['prefix'],
                $config
            );
        });
        
        Log::info('Database connection pooling enabled');
    }
    
    /**
     * Get DSN for MySQL connection
     */
    private function getDsn(array $config): string
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3306;
        $database = $config['database'];
        $charset = $config['charset'] ?? 'utf8mb4';
        
        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }
    
    /**
     * Get default pool configuration
     */
    private function getPoolConfig(): array
    {
        return [
            'database' => [
                'pool' => [
                    'min_connections' => env('DB_POOL_MIN_CONNECTIONS', 5),
                    'max_connections' => env('DB_POOL_MAX_CONNECTIONS', 100),
                    'max_idle_time' => env('DB_POOL_MAX_IDLE_TIME', 300),
                    'health_check_interval' => env('DB_POOL_HEALTH_CHECK_INTERVAL', 60),
                    'acquire_timeout' => env('DB_POOL_ACQUIRE_TIMEOUT', 5),
                ],
            ],
        ];
    }
    
    /**
     * Register health check route
     */
    private function registerHealthCheckRoute(): void
    {
        if (!$this->app->routesAreCached()) {
            $this->app['router']->get('/_health/database-pool', function () {
                $poolManager = app(ConnectionPoolManager::class);
                
                return response()->json([
                    'status' => 'ok',
                    'timestamp' => now()->toIso8601String(),
                    'pools' => $poolManager->getStats(),
                    'health' => $poolManager->healthCheck(),
                ]);
            })->name('health.database-pool');
        }
    }
}