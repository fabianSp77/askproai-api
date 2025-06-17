<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Alerts\AlertManager;
use Illuminate\Support\Facades\Log;

class RunHealthChecks extends Command
{
    protected $signature = 'health:check';
    protected $description = 'Run system health checks and send alerts if needed';

    public function handle()
    {
        Log::info('Running scheduled health check');
        
        try {
            $alertManager = new AlertManager();
            $alerts = $alertManager->checkSystemHealth();
            
            if (empty($alerts)) {
                Log::info('Health check completed - system healthy');
                $this->info('✅ System is healthy');
            } else {
                Log::warning('Health check found issues', [
                    'alert_count' => count($alerts),
                    'alerts' => $alerts,
                ]);
                
                $this->warn('⚠️  ' . count($alerts) . ' issue(s) found');
                
                foreach ($alerts as $alert) {
                    $this->error($alert['service'] . ' - ' . $alert['type'] . ': ' . $alert['message']);
                }
            }
            
            // Update health check timestamp
            \Cache::put('last_health_check', now(), now()->addHours(24));
            
        } catch (\Exception $e) {
            Log::error('Health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->error('Health check failed: ' . $e->getMessage());
            
            // Try to send alert about health check failure
            try {
                $alertManager = new AlertManager();
                $alertManager->sendCriticalAlert(
                    'system',
                    'health_check_failed',
                    'Scheduled health check failed to complete',
                    ['error' => $e->getMessage()]
                );
            } catch (\Exception $alertException) {
                Log::critical('Failed to send health check failure alert', [
                    'error' => $alertException->getMessage(),
                ]);
            }
            
            return 1;
        }
        
        return 0;
    }
}