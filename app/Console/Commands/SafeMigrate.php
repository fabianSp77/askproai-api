<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class SafeMigrate extends Command
{
    protected $signature = 'migrate:safe 
                            {--force : Force the operation to run in production}
                            {--dry-run : Show what would be done without executing}
                            {--backup : Create backup before migration}';

    protected $description = 'Safe migration with automatic backup and rollback capability';

    public function handle()
    {
        if (!$this->option('force') && app()->environment('production')) {
            $this->error('❌ Running migrations in production requires --force flag');
            return 1;
        }
        
        // Check for destructive migrations
        $this->checkForDestructiveMigrations();
        
        // Create backup if requested
        if ($this->option('backup')) {
            $this->info('Creating backup before migration...');
            $result = Artisan::call('askproai:backup', ['--type' => 'full', '--compress' => true]);
            
            if ($result !== 0) {
                $this->error('❌ Backup failed! Migration aborted.');
                return 1;
            }
        }
        
        // Dry run mode
        if ($this->option('dry-run')) {
            $this->info('🔍 Dry run mode - showing pending migrations:');
            Artisan::call('migrate:status');
            $this->info(Artisan::output());
            return 0;
        }
        
        // Execute migrations with transaction
        DB::beginTransaction();
        
        try {
            $this->info('Running migrations...');
            Artisan::call('migrate', ['--force' => $this->option('force')]);
            
            DB::commit();
            $this->info('✅ Migrations completed successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Migration failed: ' . $e->getMessage());
            $this->error('Database rolled back to previous state.');
            
            // Log the error
            \Log::error('Migration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
        
        return 0;
    }
    
    private function checkForDestructiveMigrations()
    {
        $pendingMigrations = $this->getPendingMigrations();
        $destructive = [];
        
        foreach ($pendingMigrations as $migration) {
            $content = file_get_contents(database_path("migrations/{$migration}.php"));
            
            // Check for dangerous operations
            if (preg_match('/(DROP TABLE|TRUNCATE|DELETE FROM|dropIfExists)/i', $content)) {
                $destructive[] = $migration;
            }
        }
        
        if (!empty($destructive)) {
            $this->warn('⚠️  WARNING: Destructive migrations detected:');
            foreach ($destructive as $migration) {
                $this->warn("   - {$migration}");
            }
            
            if (!$this->confirm('Do you really want to run these destructive migrations?')) {
                $this->error('Migration cancelled by user.');
                exit(1);
            }
        }
    }
    
    private function getPendingMigrations()
    {
        $files = glob(database_path('migrations/*.php'));
        $run = DB::table('migrations')->pluck('migration')->toArray();
        
        $pending = [];
        foreach ($files as $file) {
            $name = str_replace('.php', '', basename($file));
            if (!in_array($name, $run)) {
                $pending[] = $name;
            }
        }
        
        return $pending;
    }
}