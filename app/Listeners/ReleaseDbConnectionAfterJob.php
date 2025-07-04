<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\DB;
use App\Database\PooledMySqlConnector;
use Illuminate\Support\Facades\Log;

/**
 * Listener to release database connections after queue job completion
 */
class ReleaseDbConnectionAfterJob
{
    /**
     * Handle job processed event.
     *
     * @param  JobProcessed  $event
     * @return void
     */
    public function handleJobProcessed(JobProcessed $event): void
    {
        $this->releaseConnections('processed', $event->job->getName());
    }
    
    /**
     * Handle job failed event.
     *
     * @param  JobFailed  $event
     * @return void
     */
    public function handleJobFailed(JobFailed $event): void
    {
        $this->releaseConnections('failed', $event->job->getName());
    }
    
    /**
     * Release all database connections back to the pool
     */
    protected function releaseConnections(string $status, string $jobName): void
    {
        try {
            // Get all database connections
            $connections = DB::getConnections();
            $releasedCount = 0;
            
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
                            Log::warning('ReleaseDbConnectionAfterJob: Rolled back open transaction', [
                                'connection' => $name,
                                'job' => $jobName,
                                'status' => $status
                            ]);
                        }
                        
                        // Return to pool if using pooled connector
                        if (config('database.pool.enabled', true)) {
                            $config = config("database.connections.{$name}");
                            if ($config && $config['driver'] === 'mysql') {
                                PooledMySqlConnector::returnToPool($pdo, $config);
                                $releasedCount++;
                            }
                        }
                    }
                    
                    // Disconnect to free the connection
                    DB::disconnect($name);
                }
            }
            
            // Log connection release for monitoring
            if ($releasedCount > 0 || config('app.debug')) {
                Log::debug('Queue job database connections released', [
                    'job' => $jobName,
                    'status' => $status,
                    'connections_released' => $releasedCount,
                    'stats' => PooledMySqlConnector::getStats()
                ]);
            }
        } catch (\Exception $e) {
            // Don't let connection release errors affect job processing
            Log::error('Error releasing database connections after job', [
                'job' => $jobName,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
        }
    }
}