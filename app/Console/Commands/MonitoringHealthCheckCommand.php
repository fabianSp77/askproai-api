<?php

namespace App\Console\Commands;

use App\Services\Monitoring\UnifiedAlertingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonitoringHealthCheckCommand extends Command
{
    protected $signature = 'monitoring:health-check 
                            {--force : Force check even if recently checked}
                            {--dry-run : Run checks without sending alerts}';

    protected $description = 'Run system health checks and trigger alerts if needed';

    private UnifiedAlertingService $alertingService;

    public function __construct(UnifiedAlertingService $alertingService)
    {
        parent::__construct();
        $this->alertingService = $alertingService;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔍 Running AskProAI System Health Check...');
        $this->newLine();

        // Run health checks
        $startTime = microtime(true);
        $alerts = $this->alertingService->checkSystemHealth();
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if (empty($alerts)) {
            $this->info('✅ All systems operational! No issues detected.');
        } else {
            $this->warn('⚠️ ' . count($alerts) . ' issues detected:');
            $this->newLine();

            foreach ($alerts as $alert) {
                $this->displayAlert($alert);
            }
        }

        // Display system metrics
        $this->newLine();
        $this->info('📊 System Metrics:');
        $this->displaySystemMetrics();

        // Display recent alerts
        $this->newLine();
        $this->info('🚨 Recent Alerts (last 24 hours):');
        $this->displayRecentAlerts();

        $this->newLine();
        $this->info("Health check completed in {$duration}ms");

        return empty($alerts) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Display alert information.
     */
    private function displayAlert(array $alert): void
    {
        $severity = $this->getSeverityLabel($alert['rule']);
        $this->line("  {$severity} {$alert['rule']}");

        if (! empty($alert['data'])) {
            foreach ($alert['data'] as $key => $value) {
                $this->line("    - {$key}: {$value}");
            }
        }
    }

    /**
     * Get severity label with color.
     */
    private function getSeverityLabel(string $rule): string
    {
        $severityConfig = config("monitoring.alerts.rules.{$rule}.severity", 'medium');

        return match ($severityConfig) {
            'critical' => '<fg=red;options=bold>[CRITICAL]</>',
            'high' => '<fg=yellow;options=bold>[HIGH]</>',
            'medium' => '<fg=cyan>[MEDIUM]</>',
            'low' => '<fg=green>[LOW]</>',
            default => '[UNKNOWN]',
        };
    }

    /**
     * Display system metrics.
     */
    private function displaySystemMetrics(): void
    {
        $metrics = [];

        // API success rate
        $apiStats = DB::table('api_call_logs')
            ->where('created_at', '>=', now()->subHour())
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status_code < 400 THEN 1 ELSE 0 END) as success')
            ->first();

        if ($apiStats && $apiStats->total > 0) {
            $successRate = round(($apiStats->success / $apiStats->total) * 100, 2);
            $metrics[] = ['API Success Rate', "{$successRate}%", $this->getStatusIndicator($successRate, 90)];
        }

        // Average response time
        $avgResponseTime = DB::table('api_call_logs')
            ->where('created_at', '>=', now()->subHour())
            ->avg('duration_ms');

        if ($avgResponseTime) {
            $avgResponseTime = round($avgResponseTime);
            $metrics[] = ['Avg Response Time', "{$avgResponseTime}ms", $this->getStatusIndicator(2000 - $avgResponseTime, 1800)];
        }

        // Queue size
        $queueSize = DB::table('jobs')->count();
        $metrics[] = ['Queue Size', $queueSize, $this->getStatusIndicator(1000 - $queueSize, 900)];

        // Active webhooks
        $activeWebhooks = DB::table('webhook_events')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();
        $metrics[] = ['Recent Webhooks', $activeWebhooks, '📨'];

        // Database size
        $dbSize = DB::select('
            SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb 
            FROM information_schema.tables 
            WHERE table_schema = ?
        ', [config('database.connections.mysql.database')])[0]->size_mb ?? 0;

        $metrics[] = ['Database Size', round($dbSize) . 'MB', $this->getStatusIndicator(5000 - $dbSize, 4000)];

        $this->table(['Metric', 'Value', 'Status'], $metrics);
    }

    /**
     * Get status indicator.
     */
    private function getStatusIndicator(float $value, float $threshold): string
    {
        if ($value >= $threshold) {
            return '✅';
        } elseif ($value >= $threshold * 0.8) {
            return '⚠️';
        } else {
            return '❌';
        }
    }

    /**
     * Display recent alerts.
     */
    private function displayRecentAlerts(): void
    {
        $recentAlerts = $this->alertingService->getActiveAlerts(24);

        if (empty($recentAlerts)) {
            $this->line('  No alerts in the last 24 hours');

            return;
        }

        $alertsTable = [];
        foreach (array_slice($recentAlerts, 0, 10) as $alert) {
            $alertsTable[] = [
                $alert->created_at,
                $alert->rule,
                strtoupper($alert->severity),
                substr($alert->message, 0, 50) . (strlen($alert->message) > 50 ? '...' : ''),
            ];
        }

        $this->table(['Time', 'Rule', 'Severity', 'Message'], $alertsTable);

        if (count($recentAlerts) > 10) {
            $this->line('  ... and ' . (count($recentAlerts) - 10) . ' more alerts');
        }
    }
}
