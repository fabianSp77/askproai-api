<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CalcomMigrationService;

class CalcomMigrationStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcom:migration-status 
                            {--migrate= : Migrate specific feature to V2}
                            {--rollback= : Rollback specific feature to V1}
                            {--health : Run health check}
                            {--report : Generate detailed migration report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor and manage Cal.com V1 to V2 migration progress';

    protected CalcomMigrationService $migrationService;

    /**
     * Execute the console command.
     */
    public function handle(CalcomMigrationService $migrationService): int
    {
        $this->migrationService = $migrationService;
        
        // Handle specific actions
        if ($feature = $this->option('migrate')) {
            return $this->migrateFeature($feature);
        }
        
        if ($feature = $this->option('rollback')) {
            return $this->rollbackFeature($feature);
        }
        
        if ($this->option('health')) {
            return $this->runHealthCheck();
        }
        
        if ($this->option('report')) {
            return $this->generateReport();
        }
        
        // Default: Show migration status
        return $this->showStatus();
    }
    
    private function showStatus(): int
    {
        $report = $this->migrationService->getMigrationReport();
        
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║             Cal.com V1 → V2 Migration Status              ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        // Overall progress bar
        $progress = $report['overall_progress'];
        $this->info("Overall Progress: {$progress}%");
        $this->output->progressStart(100);
        $this->output->progressAdvance((int)$progress);
        $this->output->progressFinish();
        $this->newLine();
        
        // Deprecation warning
        $daysLeft = $report['days_until_deprecation'];
        $warningLevel = $daysLeft < 90 ? 'error' : ($daysLeft < 180 ? 'warn' : 'info');
        $this->$warningLevel("⚠️  V1 API deprecation in {$daysLeft} days (2025-12-31)");
        $this->newLine();
        
        // API Status Table
        $this->info('API Endpoint Status:');
        $headers = ['Feature', 'Status', 'V2 Ready'];
        $rows = [];
        
        foreach ($report['api_status'] as $feature => $status) {
            $statusEmoji = match($status) {
                'v2_ready' => '✅',
                'hybrid' => '🔄',
                'v1_only' => '❌',
                default => '❓'
            };
            
            $v2Ready = $report['feature_flags']["use_v2_{$feature}"] ?? false;
            $rows[] = [
                ucfirst(str_replace('_', ' ', $feature)),
                $status,
                $v2Ready ? '✅ Yes' : '❌ No'
            ];
        }
        
        $this->table($headers, $rows);
        
        // Metrics
        if (!empty($report['metrics'])) {
            $this->info('Migration Metrics (Today):');
            $this->line("  V1 API Calls: " . $report['metrics']['v1_calls_today']);
            $this->line("  V2 API Calls: " . $report['metrics']['v2_calls_today']);
            $this->line("  Fallback Count: " . $report['metrics']['fallback_count']);
            $this->line("  Error Rate: " . $report['metrics']['error_rate'] . '%');
        }
        
        // Recommendations
        if (!empty($report['recommendations'])) {
            $this->newLine();
            $this->warn('📋 Recommendations:');
            foreach ($report['recommendations'] as $rec) {
                $icon = $rec['priority'] === 'critical' ? '🔴' : '🟡';
                $this->line("{$icon} [{$rec['priority']}] {$rec['action']}");
                $this->line("   Reason: {$rec['reason']}");
            }
        }
        
        $this->newLine();
        $this->info('Run with --help for more options');
        
        return Command::SUCCESS;
    }
    
    private function migrateFeature(string $feature): int
    {
        $this->info("Migrating feature '{$feature}' to V2...");
        
        try {
            if ($this->migrationService->migrateFeature($feature)) {
                $this->info("✅ Successfully migrated '{$feature}' to V2!");
                return Command::SUCCESS;
            } else {
                $this->error("❌ Failed to migrate '{$feature}' to V2");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function rollbackFeature(string $feature): int
    {
        $this->warn("⚠️  Rolling back feature '{$feature}' to V1...");
        
        if (!$this->confirm('This should only be used in emergencies. Continue?')) {
            return Command::SUCCESS;
        }
        
        if ($this->migrationService->rollbackFeature($feature)) {
            $this->warn("Feature '{$feature}' rolled back to V1");
            return Command::SUCCESS;
        }
        
        return Command::FAILURE;
    }
    
    private function runHealthCheck(): int
    {
        $this->info('Running Cal.com API health check...');
        
        $health = $this->migrationService->healthCheck();
        
        // V1 Status
        $v1Icon = $health['v1_status'] === 'healthy' ? '✅' : '❌';
        $this->line("V1 API Status: {$v1Icon} {$health['v1_status']}");
        
        // V2 Status
        $v2Icon = $health['v2_status'] === 'healthy' ? '✅' : '❌';
        $this->line("V2 API Status: {$v2Icon} {$health['v2_status']}");
        
        // Migration Readiness
        $readyIcon = $health['migration_ready'] ? '✅' : '❌';
        $this->line("Migration Ready: {$readyIcon}");
        
        // Issues
        if (!empty($health['issues'])) {
            $this->newLine();
            $this->error('Issues detected:');
            foreach ($health['issues'] as $issue) {
                $this->line("  • {$issue}");
            }
        }
        
        return $health['migration_ready'] ? Command::SUCCESS : Command::FAILURE;
    }
    
    private function generateReport(): int
    {
        $this->info('Generating detailed migration report...');
        
        $report = $this->migrationService->getMigrationReport();
        $filename = storage_path('logs/calcom-migration-report-' . date('Y-m-d-His') . '.json');
        
        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));
        
        $this->info("Report saved to: {$filename}");
        
        // Also display summary
        $this->newLine();
        $this->info('Summary:');
        $this->line("  Progress: {$report['overall_progress']}%");
        $this->line("  Days until deprecation: {$report['days_until_deprecation']}");
        $this->line("  Critical issues: " . count(array_filter($report['recommendations'], fn($r) => $r['priority'] === 'critical')));
        
        return Command::SUCCESS;
    }
}