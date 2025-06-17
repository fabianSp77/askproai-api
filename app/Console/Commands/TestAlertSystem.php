<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Alerts\AlertManager;

class TestAlertSystem extends Command
{
    protected $signature = 'test:alert-system {--type=manual : Type of test (manual, health-check)}';
    protected $description = 'Test the alert system';

    public function handle()
    {
        $type = $this->option('type');
        $alertManager = new AlertManager();
        
        $this->info("🚨 TESTING ALERT SYSTEM");
        $this->info("======================\n");
        
        if ($type === 'manual') {
            $this->testManualAlert($alertManager);
        } else {
            $this->testHealthCheck($alertManager);
        }
    }
    
    private function testManualAlert(AlertManager $alertManager): void
    {
        $this->info("1. SENDING TEST ALERT");
        $this->info("--------------------");
        
        $service = $this->choice('Select service', ['calcom', 'retell', 'system', 'database'], 0);
        $errorType = $this->choice('Select error type', [
            'api_down',
            'api_degraded',
            'circuit_breaker_open',
            'high_error_rate',
            'slow_response',
            'disk_space_low',
            'test_alert'
        ], 6);
        
        $message = $this->ask('Enter alert message', 'This is a test alert from the AskProAI monitoring system');
        
        $context = [
            'test' => true,
            'triggered_by' => 'console_command',
            'user' => auth()->user()?->email ?? 'system',
            'timestamp' => now()->toIso8601String(),
        ];
        
        $this->info("\nSending alert...");
        
        try {
            $alertManager->sendCriticalAlert($service, $errorType, $message, $context);
            
            $this->info("✅ Alert sent successfully!");
            $this->info("\nDetails:");
            $this->table(
                ['Property', 'Value'],
                [
                    ['Service', $service],
                    ['Type', $errorType],
                    ['Message', $message],
                    ['Context', json_encode($context, JSON_PRETTY_PRINT)],
                ]
            );
            
            // Check if alert was stored
            $latestAlert = \DB::table('system_alerts')
                ->where('service', $service)
                ->where('type', $errorType)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($latestAlert) {
                $this->info("\n✅ Alert stored in database with ID: " . $latestAlert->id);
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to send alert: " . $e->getMessage());
        }
    }
    
    private function testHealthCheck(AlertManager $alertManager): void
    {
        $this->info("2. RUNNING HEALTH CHECK");
        $this->info("----------------------");
        
        $this->info("\nChecking system health and generating alerts if needed...\n");
        
        try {
            $alerts = $alertManager->checkSystemHealth();
            
            if (empty($alerts)) {
                $this->info("✅ System is healthy - no alerts generated");
            } else {
                $this->warn("⚠️  " . count($alerts) . " alert(s) generated:");
                
                foreach ($alerts as $alert) {
                    $this->error("\n🚨 " . strtoupper($alert['service']) . " - " . $alert['type']);
                    $this->line("   " . $alert['message']);
                    
                    if (!empty($alert['context'])) {
                        $this->line("   Context: " . json_encode($alert['context'], JSON_PRETTY_PRINT));
                    }
                }
            }
            
            // Show current system metrics
            $this->info("\n3. CURRENT SYSTEM METRICS");
            $this->info("------------------------");
            
            // API metrics
            $hourAgo = now()->subHour();
            $apiMetrics = \DB::table('circuit_breaker_metrics')
                ->select('service', \DB::raw('COUNT(*) as total'), \DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success'))
                ->where('created_at', '>=', $hourAgo)
                ->groupBy('service')
                ->get();
            
            $this->info("\nAPI Health (last hour):");
            foreach ($apiMetrics as $metric) {
                $successRate = $metric->total > 0 ? round(($metric->success / $metric->total) * 100, 2) : 100;
                $status = $successRate >= 90 ? '✅' : ($successRate >= 70 ? '⚠️' : '❌');
                $this->line("  {$status} {$metric->service}: {$successRate}% success ({$metric->success}/{$metric->total} calls)");
            }
            
            // Error count
            $errorCount = \DB::table('critical_errors')
                ->where('created_at', '>=', now()->subMinutes(15))
                ->count();
            
            $this->info("\nError Rate:");
            $this->line("  Critical errors (15 min): {$errorCount}");
            
            // Disk space
            $diskFreeSpace = disk_free_space('/');
            $diskTotalSpace = disk_total_space('/');
            $diskUsagePercent = round((($diskTotalSpace - $diskFreeSpace) / $diskTotalSpace) * 100, 1);
            $freeSpaceGB = round($diskFreeSpace / 1024 / 1024 / 1024, 2);
            
            $this->info("\nSystem Resources:");
            $this->line("  Disk usage: {$diskUsagePercent}% ({$freeSpaceGB} GB free)");
            
        } catch (\Exception $e) {
            $this->error("❌ Health check failed: " . $e->getMessage());
        }
    }
}