<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\Company;
use App\Notifications\SystemAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class MonitoringHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:health-check 
                            {--alert : Send alerts for critical issues}
                            {--metrics : Export metrics to file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Führt System Health Checks durch und triggert Alerts';

    protected array $alerts = [];
    protected array $metrics = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏥 Running System Health Check...');
        
        // Collect all metrics
        $this->checkDatabaseHealth();
        $this->checkQueueHealth();
        $this->checkApiHealth();
        $this->checkBusinessMetrics();
        $this->checkSystemResources();
        
        // Process alerts if needed
        if ($this->option('alert') && count($this->alerts) > 0) {
            $this->sendAlerts();
        }
        
        // Export metrics if requested
        if ($this->option('metrics')) {
            $this->exportMetrics();
        }
        
        // Store metrics in cache for dashboard
        Cache::put('system_health_metrics', $this->metrics, 300);
        
        // Display summary
        $this->displaySummary();
        
        return count($this->alerts) > 0 ? 1 : 0;
    }

    /**
     * Check database health
     */
    protected function checkDatabaseHealth(): void
    {
        try {
            // Check connection
            $start = microtime(true);
            DB::select('SELECT 1');
            $connectionTime = round((microtime(true) - $start) * 1000, 2);
            
            $this->metrics['database']['connection_time'] = $connectionTime;
            
            if ($connectionTime > 500) {
                $this->addAlert('critical', 'Database response time > 500ms', [
                    'response_time' => $connectionTime
                ]);
            }
            
            // Check connection pool
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'");
            
            $currentConnections = (int) $connections[0]->Value;
            $maxAllowed = (int) $maxConnections[0]->Value;
            $connectionUsage = ($currentConnections / $maxAllowed) * 100;
            
            $this->metrics['database']['connection_usage'] = round($connectionUsage, 2);
            
            if ($connectionUsage > 80) {
                $this->addAlert('warning', 'Database connection pool > 80%', [
                    'current' => $currentConnections,
                    'max' => $maxAllowed
                ]);
            }
            
            // Check slow queries
            $slowQueries = DB::table('information_schema.processlist')
                ->where('command', '!=', 'Sleep')
                ->where('time', '>', 5)
                ->count();
                
            $this->metrics['database']['slow_queries'] = $slowQueries;
            
            if ($slowQueries > 5) {
                $this->addAlert('warning', 'Multiple slow queries detected', [
                    'count' => $slowQueries
                ]);
            }
            
            $this->info('✅ Database health check completed');
            
        } catch (\Exception $e) {
            $this->addAlert('critical', 'Database health check failed', [
                'error' => $e->getMessage()
            ]);
            $this->error('❌ Database health check failed: ' . $e->getMessage());
        }
    }

    /**
     * Check queue system health
     */
    protected function checkQueueHealth(): void
    {
        try {
            // Check Horizon status
            $horizonStatus = trim(shell_exec('php artisan horizon:status 2>&1'));
            $isRunning = str_contains($horizonStatus, 'running');
            
            $this->metrics['queue']['horizon_running'] = $isRunning;
            
            if (!$isRunning) {
                $this->addAlert('critical', 'Horizon is not running', [
                    'status' => $horizonStatus
                ]);
            }
            
            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')->count();
            $recentFailures = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHour())
                ->count();
                
            $this->metrics['queue']['failed_jobs'] = $failedJobs;
            $this->metrics['queue']['recent_failures'] = $recentFailures;
            
            if ($recentFailures > 10) {
                $this->addAlert('warning', 'High number of recent job failures', [
                    'count' => $recentFailures
                ]);
            }
            
            // Check queue sizes
            $queues = ['default', 'webhooks', 'calls', 'notifications'];
            $totalQueueSize = 0;
            
            foreach ($queues as $queue) {
                try {
                    $size = \Illuminate\Support\Facades\Redis::llen("queues:$queue");
                    $this->metrics['queue']['sizes'][$queue] = $size;
                    $totalQueueSize += $size;
                    
                    if ($size > 1000) {
                        $this->addAlert('warning', "Queue '$queue' has high backlog", [
                            'size' => $size
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->metrics['queue']['sizes'][$queue] = 0;
                }
            }
            
            $this->info('✅ Queue health check completed');
            
        } catch (\Exception $e) {
            $this->addAlert('critical', 'Queue health check failed', [
                'error' => $e->getMessage()
            ]);
            $this->error('❌ Queue health check failed: ' . $e->getMessage());
        }
    }

    /**
     * Check external API health
     */
    protected function checkApiHealth(): void
    {
        $apis = [
            'calcom' => 'https://api.cal.com/v2/health',
            'retell' => 'https://api.retellai.com',
            'stripe' => 'https://api.stripe.com',
        ];
        
        foreach ($apis as $name => $endpoint) {
            try {
                $start = microtime(true);
                $response = Http::timeout(10)->get($endpoint);
                $responseTime = round((microtime(true) - $start) * 1000, 2);
                
                $this->metrics['api'][$name] = [
                    'status' => $response->successful() ? 'online' : 'error',
                    'response_time' => $responseTime,
                    'status_code' => $response->status(),
                ];
                
                if (!$response->successful()) {
                    $this->addAlert('warning', "$name API returned error", [
                        'status_code' => $response->status(),
                        'endpoint' => $endpoint
                    ]);
                }
                
                if ($responseTime > 3000) {
                    $this->addAlert('warning', "$name API slow response", [
                        'response_time' => $responseTime
                    ]);
                }
                
            } catch (\Exception $e) {
                $this->metrics['api'][$name] = [
                    'status' => 'offline',
                    'error' => $e->getMessage()
                ];
                
                $this->addAlert('critical', "$name API unreachable", [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->info('✅ API health check completed');
    }

    /**
     * Check business metrics
     */
    protected function checkBusinessMetrics(): void
    {
        try {
            // Check for stale calls
            $staleCalls = Call::where('status', 'in_progress')
                ->where('created_at', '<', now()->subHours(2))
                ->count();
                
            $this->metrics['business']['stale_calls'] = $staleCalls;
            
            if ($staleCalls > 0) {
                $this->addAlert('warning', 'Stale calls detected', [
                    'count' => $staleCalls
                ]);
            }
            
            // Check appointment conflicts
            $conflicts = DB::select("
                SELECT COUNT(*) as count
                FROM appointments a1
                JOIN appointments a2 ON a1.id != a2.id
                WHERE a1.staff_id = a2.staff_id
                AND a1.status = 'scheduled'
                AND a2.status = 'scheduled'
                AND a1.starts_at < a2.ends_at
                AND a2.starts_at < a1.ends_at
                AND DATE(a1.starts_at) = CURDATE()
            ");
            
            $conflictCount = $conflicts[0]->count ?? 0;
            $this->metrics['business']['appointment_conflicts'] = $conflictCount;
            
            if ($conflictCount > 0) {
                $this->addAlert('critical', 'Appointment conflicts detected today', [
                    'count' => $conflictCount
                ]);
            }
            
            // Check inactive companies with active subscriptions
            $inactiveWithSubs = Company::where('subscription_status', 'active')
                ->whereDoesntHave('calls', function ($query) {
                    $query->where('created_at', '>=', now()->subDays(7));
                })
                ->count();
                
            $this->metrics['business']['inactive_companies'] = $inactiveWithSubs;
            
            if ($inactiveWithSubs > 5) {
                $this->addAlert('info', 'Companies with active subscriptions but no recent activity', [
                    'count' => $inactiveWithSubs
                ]);
            }
            
            $this->info('✅ Business metrics check completed');
            
        } catch (\Exception $e) {
            $this->error('❌ Business metrics check failed: ' . $e->getMessage());
        }
    }

    /**
     * Check system resources
     */
    protected function checkSystemResources(): void
    {
        try {
            // Disk usage
            $diskTotal = disk_total_space('/');
            $diskFree = disk_free_space('/');
            $diskUsage = (($diskTotal - $diskFree) / $diskTotal) * 100;
            
            $this->metrics['system']['disk_usage'] = round($diskUsage, 2);
            
            if ($diskUsage > 90) {
                $this->addAlert('critical', 'Disk usage critical', [
                    'usage' => round($diskUsage, 2) . '%'
                ]);
            } elseif ($diskUsage > 80) {
                $this->addAlert('warning', 'Disk usage high', [
                    'usage' => round($diskUsage, 2) . '%'
                ]);
            }
            
            // Memory usage
            $memInfo = $this->getMemoryInfo();
            $memUsage = ($memInfo['used'] / $memInfo['total']) * 100;
            
            $this->metrics['system']['memory_usage'] = round($memUsage, 2);
            
            if ($memUsage > 90) {
                $this->addAlert('critical', 'Memory usage critical', [
                    'usage' => round($memUsage, 2) . '%'
                ]);
            }
            
            // Load average
            $loadAvg = sys_getloadavg();
            $cpuCount = (int) shell_exec('nproc');
            $loadPerCpu = $loadAvg[0] / $cpuCount;
            
            $this->metrics['system']['load_average'] = [
                '1m' => round($loadAvg[0], 2),
                '5m' => round($loadAvg[1], 2),
                '15m' => round($loadAvg[2], 2),
            ];
            
            if ($loadPerCpu > 2) {
                $this->addAlert('warning', 'High system load', [
                    'load_average' => $loadAvg[0],
                    'cpu_count' => $cpuCount
                ]);
            }
            
            $this->info('✅ System resources check completed');
            
        } catch (\Exception $e) {
            $this->error('❌ System resources check failed: ' . $e->getMessage());
        }
    }

    /**
     * Get memory information
     */
    protected function getMemoryInfo(): array
    {
        $free = shell_exec('free -b');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        
        return [
            'total' => (int) $mem[1],
            'used' => (int) $mem[2],
            'free' => (int) $mem[3],
        ];
    }

    /**
     * Add alert
     */
    protected function addAlert(string $level, string $message, array $context = []): void
    {
        $this->alerts[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toIso8601String(),
        ];
        
        // Log the alert
        Log::channel('monitoring')->log($level, $message, $context);
    }

    /**
     * Send alerts via notification channels
     */
    protected function sendAlerts(): void
    {
        $criticalAlerts = collect($this->alerts)->where('level', 'critical');
        
        if ($criticalAlerts->isNotEmpty()) {
            // Send to admin users
            $admins = \App\Models\User::role(['super_admin', 'admin'])->get();
            
            foreach ($admins as $admin) {
                try {
                    Notification::send($admin, new SystemAlertNotification($criticalAlerts->toArray()));
                } catch (\Exception $e) {
                    $this->error('Failed to send notification: ' . $e->getMessage());
                }
            }
        }
        
        $this->info('📧 Alerts sent: ' . count($this->alerts));
    }

    /**
     * Export metrics to file
     */
    protected function exportMetrics(): void
    {
        $filename = storage_path('monitoring/health-check-' . now()->format('Y-m-d-H-i-s') . '.json');
        $directory = dirname($filename);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $data = [
            'timestamp' => now()->toIso8601String(),
            'metrics' => $this->metrics,
            'alerts' => $this->alerts,
        ];
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        
        $this->info('📊 Metrics exported to: ' . $filename);
    }

    /**
     * Display summary
     */
    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('📊 Health Check Summary');
        $this->line('======================');
        
        $alertsByLevel = collect($this->alerts)->groupBy('level');
        
        $this->table(
            ['Level', 'Count'],
            [
                ['Critical', $alertsByLevel->get('critical', collect())->count()],
                ['Warning', $alertsByLevel->get('warning', collect())->count()],
                ['Info', $alertsByLevel->get('info', collect())->count()],
            ]
        );
        
        if (count($this->alerts) === 0) {
            $this->info('✅ All systems operational!');
        } else {
            $this->warn('⚠️  ' . count($this->alerts) . ' issues detected');
            
            foreach ($this->alerts as $alert) {
                $icon = match($alert['level']) {
                    'critical' => '🔴',
                    'warning' => '🟡',
                    'info' => '🔵',
                    default => '⚪',
                };
                
                $this->line("$icon {$alert['message']}");
            }
        }
    }
}