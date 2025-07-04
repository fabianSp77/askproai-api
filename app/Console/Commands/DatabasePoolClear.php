<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Database\PooledMySqlConnector;

class DatabasePoolClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:pool-clear {--force : Force clear without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all connections from the database pool';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to clear all database connections from the pool?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }
        
        // Get stats before clearing
        $statsBefore = PooledMySqlConnector::getStats();
        
        // Clear the pool
        PooledMySqlConnector::clearPool();
        
        // Get stats after clearing
        $statsAfter = PooledMySqlConnector::getStats();
        
        $this->info('Database connection pool cleared successfully!');
        $this->info('');
        $this->info('Before:');
        $this->info('- Pool Size: ' . $statsBefore['pool_size']);
        $this->info('- Connections Created: ' . $statsBefore['connections_created']);
        $this->info('');
        $this->info('After:');
        $this->info('- Pool Size: ' . $statsAfter['pool_size']);
        
        return Command::SUCCESS;
    }
}