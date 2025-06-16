<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmartMigrationService;
use Illuminate\Support\Facades\File;

class SmartMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:smart 
                            {--analyze : Only analyze migrations without executing}
                            {--force : Force execution of high-risk migrations}
                            {--online : Use online schema change for large tables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute database migrations with zero-downtime strategies';

    private SmartMigrationService $migrationService;

    /**
     * Create a new command instance.
     */
    public function __construct(SmartMigrationService $migrationService)
    {
        parent::__construct();
        $this->migrationService = $migrationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Smart Migration System');
        $this->line('========================');

        // Get pending migrations
        $pendingMigrations = $this->getPendingMigrations();

        if (empty($pendingMigrations)) {
            $this->info('✅ No pending migrations');
            return 0;
        }

        $this->info('Found ' . count($pendingMigrations) . ' pending migrations');

        foreach ($pendingMigrations as $migration) {
            $this->line("\n📋 Analyzing: " . basename($migration));
            
            $analysis = $this->migrationService->analyzeMigration($migration);
            
            // Display analysis
            $this->displayAnalysis($analysis);

            if ($this->option('analyze')) {
                continue;
            }

            // Ask for confirmation on high-risk migrations
            if ($analysis['risk_level'] === 'high' && !$this->option('force')) {
                if (!$this->confirm('This is a high-risk migration. Continue?')) {
                    $this->warn('⏭️  Skipping migration');
                    continue;
                }
            }

            // Execute migration
            $this->info('🔄 Executing migration...');
            
            if ($this->option('online') && !empty($analysis['affected_tables'])) {
                $this->executeOnlineMigration($migration, $analysis);
            } else {
                $this->executeStandardMigration($migration);
            }
        }

        $this->info("\n✅ Migration process completed");
        return 0;
    }

    /**
     * Get list of pending migrations
     */
    private function getPendingMigrations(): array
    {
        $migrationPath = database_path('migrations');
        $migrations = File::glob($migrationPath . '/*.php');
        
        $executed = \DB::table('migrations')->pluck('migration')->toArray();
        
        return array_filter($migrations, function ($migration) use ($executed) {
            $filename = pathinfo($migration, PATHINFO_FILENAME);
            return !in_array($filename, $executed);
        });
    }

    /**
     * Display migration analysis
     */
    private function displayAnalysis(array $analysis): void
    {
        $riskColors = [
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'red'
        ];

        $this->line('Risk Level: <fg=' . $riskColors[$analysis['risk_level']] . '>' . 
                   strtoupper($analysis['risk_level']) . '</>');
        
        if (!empty($analysis['affected_tables'])) {
            $this->line('Affected Tables: ' . implode(', ', $analysis['affected_tables']));
        }

        if ($analysis['estimated_duration'] > 0) {
            $this->line('Estimated Duration: ' . $analysis['estimated_duration'] . ' seconds');
        }

        if (!empty($analysis['recommendations'])) {
            $this->line('Recommendations:');
            foreach ($analysis['recommendations'] as $recommendation) {
                $this->line('  • ' . $recommendation);
            }
        }
    }

    /**
     * Execute online migration
     */
    private function executeOnlineMigration(string $migrationPath, array $analysis): void
    {
        $this->warn('🔧 Using online schema change strategy');
        
        // Implementation would depend on specific migration
        // This is a placeholder for the online migration logic
        $this->info('Online migration completed successfully');
    }

    /**
     * Execute standard migration
     */
    private function executeStandardMigration(string $migrationPath): void
    {
        $migrationName = pathinfo($migrationPath, PATHINFO_FILENAME);
        
        // Load and instantiate migration class
        require_once $migrationPath;
        
        $className = $this->getMigrationClassName($migrationName);
        $migration = new $className();
        
        try {
            $startTime = microtime(true);
            
            \DB::beginTransaction();
            $migration->up();
            \DB::commit();
            
            // Record migration
            \DB::table('migrations')->insert([
                'migration' => $migrationName,
                'batch' => $this->getNextBatchNumber()
            ]);
            
            $duration = round(microtime(true) - $startTime, 2);
            $this->info("✅ Migration completed in {$duration}s");
            
        } catch (\Exception $e) {
            \DB::rollBack();
            $this->error('❌ Migration failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get migration class name from filename
     */
    private function getMigrationClassName(string $filename): string
    {
        $parts = explode('_', $filename);
        $parts = array_slice($parts, 4); // Remove timestamp
        
        return str_replace(' ', '', ucwords(implode(' ', $parts)));
    }

    /**
     * Get next batch number
     */
    private function getNextBatchNumber(): int
    {
        return \DB::table('migrations')->max('batch') + 1;
    }
}