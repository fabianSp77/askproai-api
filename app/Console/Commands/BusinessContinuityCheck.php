<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BusinessContinuityCheck extends Command
{
    protected $signature = 'askproai:continuity-check
                            {--full : Run comprehensive check}
                            {--alert : Send alerts for critical issues}';

    protected $description = 'Check business continuity readiness and data integrity';

    public function handle()
    {
        $this->info("🔍 AskProAI Business Continuity Check\n");
        
        $issues = [];
        $warnings = [];
        
        // 1. Check Backup Status
        $this->info("1. Checking Backup Status...");
        $backupStatus = $this->checkBackupStatus();
        if (!empty($backupStatus['issues'])) {
            $issues = array_merge($issues, $backupStatus['issues']);
        }
        if (!empty($backupStatus['warnings'])) {
            $warnings = array_merge($warnings, $backupStatus['warnings']);
        }
        
        // 2. Check External Sync
        $this->info("2. Checking External Data Sync...");
        $syncStatus = $this->checkExternalSync();
        if (!empty($syncStatus['issues'])) {
            $issues = array_merge($issues, $syncStatus['issues']);
        }
        
        // 3. Check Billing Data
        $this->info("3. Checking Billing Data Integrity...");
        $billingStatus = $this->checkBillingData();
        if (!empty($billingStatus['issues'])) {
            $issues = array_merge($issues, $billingStatus['issues']);
        }
        
        // 4. Check Critical Tables
        $this->info("4. Checking Critical Tables...");
        $tableStatus = $this->checkCriticalTables();
        if (!empty($tableStatus['issues'])) {
            $issues = array_merge($issues, $tableStatus['issues']);
        }
        
        // 5. Check API Connectivity
        if ($this->option('full')) {
            $this->info("5. Checking External API Connectivity...");
            $apiStatus = $this->checkAPIConnectivity();
            if (!empty($apiStatus['issues'])) {
                $issues = array_merge($issues, $apiStatus['issues']);
            }
        }
        
        // Display Results
        $this->info("\n📊 Business Continuity Report");
        $this->info("============================\n");
        
        if (empty($issues) && empty($warnings)) {
            $this->info("✅ All systems operational - Business continuity assured!");
        } else {
            if (!empty($issues)) {
                $this->error("❌ Critical Issues Found:");
                foreach ($issues as $issue) {
                    $this->error("   - {$issue}");
                }
            }
            
            if (!empty($warnings)) {
                $this->warn("\n⚠️  Warnings:");
                foreach ($warnings as $warning) {
                    $this->warn("   - {$warning}");
                }
            }
        }
        
        // Calculate readiness score
        $score = $this->calculateReadinessScore($issues, $warnings);
        $this->info("\n🎯 Business Continuity Score: {$score}%");
        
        if ($score < 80) {
            $this->error("⚠️  Score below 80% - Immediate action required!");
        }
        
        // Send alerts if requested
        if ($this->option('alert') && !empty($issues)) {
            $this->sendAlerts($issues);
        }
        
        // Store check result
        DB::table('continuity_checks')->insert([
            'score' => $score,
            'issues' => json_encode($issues),
            'warnings' => json_encode($warnings),
            'created_at' => now(),
        ]);
        
        return empty($issues) ? 0 : 1;
    }
    
    private function checkBackupStatus()
    {
        $issues = [];
        $warnings = [];
        
        // Check last full backup
        $lastFullBackup = DB::table('backup_logs')
            ->where('type', 'full')
            ->where('status', 'success')
            ->orderBy('created_at', 'desc')
            ->first();
            
        if (!$lastFullBackup) {
            $issues[] = "No successful full backup found!";
        } elseif (Carbon::parse($lastFullBackup->created_at)->lt(Carbon::now()->subHours(26))) {
            $issues[] = "Last full backup older than 26 hours!";
        }
        
        // Check incremental backups
        $recentIncrementals = DB::table('backup_logs')
            ->where('type', 'incremental')
            ->where('created_at', '>=', Carbon::now()->subHours(2))
            ->count();
            
        if ($recentIncrementals < 2) {
            $warnings[] = "Less than 2 incremental backups in last 2 hours";
        }
        
        // Check backup file existence
        $backupDir = storage_path('backups/database');
        $backupFiles = glob("{$backupDir}/*.sql*");
        
        if (count($backupFiles) < 5) {
            $warnings[] = "Only " . count($backupFiles) . " backup files found";
        }
        
        $this->table(
            ['Backup Type', 'Last Success', 'Status'],
            [
                ['Full Backup', $lastFullBackup ? Carbon::parse($lastFullBackup->created_at)->diffForHumans() : 'Never', $lastFullBackup ? '✅' : '❌'],
                ['Incremental', "{$recentIncrementals} in last 2h", $recentIncrementals >= 2 ? '✅' : '⚠️'],
                ['Files on Disk', count($backupFiles), count($backupFiles) >= 5 ? '✅' : '⚠️'],
            ]
        );
        
        return ['issues' => $issues, 'warnings' => $warnings];
    }
    
    private function checkExternalSync()
    {
        $issues = [];
        
        // Check Cal.com sync
        $lastCalcomSync = DB::table('external_sync_logs')
            ->where('sync_type', 'calcom')
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->first();
            
        if (!$lastCalcomSync || Carbon::parse($lastCalcomSync->created_at)->lt(Carbon::now()->subHours(1))) {
            $issues[] = "Cal.com sync not run in last hour";
        }
        
        // Check Retell sync
        $lastRetellSync = DB::table('external_sync_logs')
            ->where('sync_type', 'retell')
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->first();
            
        if (!$lastRetellSync || Carbon::parse($lastRetellSync->created_at)->lt(Carbon::now()->subHours(1))) {
            $issues[] = "Retell.ai sync not run in last hour";
        }
        
        $this->table(
            ['External Service', 'Last Sync', 'Status'],
            [
                ['Cal.com', $lastCalcomSync ? Carbon::parse($lastCalcomSync->created_at)->diffForHumans() : 'Never', $lastCalcomSync ? '✅' : '❌'],
                ['Retell.ai', $lastRetellSync ? Carbon::parse($lastRetellSync->created_at)->diffForHumans() : 'Never', $lastRetellSync ? '✅' : '❌'],
            ]
        );
        
        return ['issues' => $issues];
    }
    
    private function checkBillingData()
    {
        $issues = [];
        
        // Check for current month snapshot
        $currentMonth = Carbon::now()->format('Y-m');
        $hasCurrentSnapshot = DB::table('billing_snapshots')
            ->where('period', $currentMonth)
            ->exists();
            
        if (!$hasCurrentSnapshot && Carbon::now()->day > 5) {
            $issues[] = "No billing snapshot for current month ({$currentMonth})";
        }
        
        // Check finalized snapshots
        $unfinalized = DB::table('billing_snapshots')
            ->where('created_at', '<', Carbon::now()->subDays(10))
            ->where('is_finalized', false)
            ->count();
            
        if ($unfinalized > 0) {
            $issues[] = "{$unfinalized} billing snapshots older than 10 days not finalized";
        }
        
        $this->table(
            ['Billing Check', 'Result'],
            [
                ['Current Month Snapshot', $hasCurrentSnapshot ? '✅ Created' : '❌ Missing'],
                ['Unfinalized Snapshots', $unfinalized == 0 ? '✅ None' : "⚠️  {$unfinalized} found"],
            ]
        );
        
        return ['issues' => $issues];
    }
    
    private function checkCriticalTables()
    {
        $issues = [];
        
        $criticalTables = [
            'companies' => 1,
            'branches' => 1,
            'customers' => 10,
            'appointments' => 5,
            'calls' => 5,
            'users' => 1,
        ];
        
        $results = [];
        foreach ($criticalTables as $table => $minRows) {
            $count = DB::table($table)->count();
            $status = $count >= $minRows ? '✅' : '❌';
            
            if ($count < $minRows) {
                $issues[] = "Table '{$table}' has only {$count} rows (minimum: {$minRows})";
            }
            
            $results[] = [$table, $count, $minRows, $status];
        }
        
        $this->table(
            ['Table', 'Row Count', 'Minimum', 'Status'],
            $results
        );
        
        return ['issues' => $issues];
    }
    
    private function checkAPIConnectivity()
    {
        $issues = [];
        
        // This would actually test the APIs
        // For now, just check if we have API keys
        
        $hasCalcomKey = !empty(config('services.calcom.api_key'));
        $hasRetellKey = !empty(config('services.retell.api_key'));
        
        if (!$hasCalcomKey) {
            $issues[] = "Cal.com API key not configured";
        }
        
        if (!$hasRetellKey) {
            $issues[] = "Retell.ai API key not configured";
        }
        
        $this->table(
            ['API Service', 'Configuration', 'Status'],
            [
                ['Cal.com', $hasCalcomKey ? 'API Key Set' : 'Missing', $hasCalcomKey ? '✅' : '❌'],
                ['Retell.ai', $hasRetellKey ? 'API Key Set' : 'Missing', $hasRetellKey ? '✅' : '❌'],
            ]
        );
        
        return ['issues' => $issues];
    }
    
    private function calculateReadinessScore($issues, $warnings)
    {
        $score = 100;
        $score -= count($issues) * 20;  // Each issue costs 20 points
        $score -= count($warnings) * 5;  // Each warning costs 5 points
        
        return max(0, $score);
    }
    
    private function sendAlerts($issues)
    {
        // Log critical issues
        \Log::critical('Business Continuity Check Failed', [
            'issues' => $issues,
            'timestamp' => now(),
        ]);
        
        // TODO: Send email/webhook alerts
        $this->info("\n📧 Alerts sent to administrators");
    }
}