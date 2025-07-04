<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Database\PooledMySqlConnector;
use Illuminate\Support\Facades\Log;

/**
 * Middleware to release database connections back to the pool after request
 */
class ReleaseDbConnection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Process the request
            $response = $next($request);
            
            return $response;
        } finally {
            // Always release connections, even if an exception occurred
            $this->releaseConnections();
        }
    }
    
    /**
     * Release all database connections back to the pool
     */
    protected function releaseConnections(): void
    {
        try {
            // Get all database connections
            $connections = DB::getConnections();
            
            foreach ($connections as $name => $connection) {
                if ($connection) {
                    // Get the PDO instance
                    $pdo = $connection->getPdo();
                    
                    if ($pdo) {
                        // Clear any active transactions
                        if ($connection->transactionLevel() > 0) {
                            // Rollback any open transactions
                            while ($connection->transactionLevel() > 0) {
                                $connection->rollBack();
                            }
                            Log::warning('ReleaseDbConnection: Rolled back open transaction', [
                                'connection' => $name
                            ]);
                        }
                        
                        // Return to pool if using pooled connector
                        if (config('database.pool.enabled', true)) {
                            $config = config("database.connections.{$name}");
                            if ($config && $config['driver'] === 'mysql') {
                                PooledMySqlConnector::returnToPool($pdo, $config);
                            }
                        }
                    }
                    
                    // Disconnect to free the connection
                    DB::disconnect($name);
                }
            }
            
            // Log connection release
            if (config('app.debug')) {
                Log::debug('Database connections released', [
                    'request_id' => request()->header('X-Request-ID'),
                    'stats' => PooledMySqlConnector::getStats()
                ]);
            }
        } catch (\Exception $e) {
            // Don't let connection release errors affect the response
            Log::error('Error releasing database connections', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}