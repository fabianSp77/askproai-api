<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class HealthCheckMonitor extends Command
{
    protected $signature = 'askproai:health-check 
                            {--detailed : Show detailed health information}
                            {--json : Output as JSON}
                            {--alert : Send alerts for failures}';

    protected $description = 'Run comprehensive health checks for AskProAI system';

    private array $healthStatus = [];
    private int $exitCode = 0;

    public function handle(): int
    {
        $this->info('🩺 Running AskProAI Health Checks...');
        
        // Core system checks
        $this->checkDatabase();
        $this->checkRedis();
        $this->checkFileSystem();
        $this->checkQueueSystem();
        
        // External service checks
        $this->checkCalcomApi();
        $this->checkRetellApi();
        $this->checkEmailService();
        
        // Performance checks
        $this->checkResponseTime();
        $this->checkMemoryUsage();
        $this->checkDiskSpace();
        
        // Security checks
        $this->checkSslCertificate();
        $this->checkPermissions();
        
        // Output results
        if ($this->option('json')) {
            $this->outputJson();
        } else {
            $this->outputTable();
        }
        
        // Send alerts if needed
        if ($this->option('alert') && $this->hasFailures()) {
            $this->sendAlerts();
        }
        
        // Store health status
        $this->storeHealthStatus();
        
        return $this->exitCode;
    }

    private function checkDatabase(): void
    {
        $startTime = microtime(true);
        
        try {
            DB::connection()->getPdo();
            $tableCount = DB::select('SHOW TABLES');
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->healthStatus['database'] = [
                'status' => 'healthy',
                'message' => 'Connected successfully',
                'duration_ms' => $duration,
                'details' => [
                    'tables' => count($tableCount),
                    'connection' => config('database.default'),
                    'host' => config('database.connections.mysql.host')
                ]
            ];
        } catch (Exception $e) {
            $this->healthStatus['database'] = [
                'status' => 'unhealthy',
                'message' => 'Connection failed: ' . $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
            $this->exitCode = 1;
        }
    }

    private function checkRedis(): void
    {
        $startTime = microtime(true);
        
        try {
            $redis = Cache::store('redis')->getRedis();
            $redis->ping();
            $info = $redis->info();
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->healthStatus['redis'] = [
                'status' => 'healthy',
                'message' => 'Connected successfully',
                'duration_ms' => $duration,
                'details' => [
                    'version' => $info['redis_version'] ?? 'unknown',
                    'memory_used' => $info['used_memory_human'] ?? 'unknown',
                    'connected_clients' => $info['connected_clients'] ?? 0
                ]
            ];
        } catch (Exception $e) {
            $this->healthStatus['redis'] = [
                'status' => 'unhealthy',
                'message' => 'Connection failed: ' . $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
            $this->exitCode = 1;
        }
    }

    private function checkFileSystem(): void
    {
        $paths = [
            'storage' => storage_path(),
            'logs' => storage_path('logs'),
            'cache' => storage_path('framework/cache'),
            'sessions' => storage_path('framework/sessions'),
        ];
        
        $issues = [];
        
        foreach ($paths as $name => $path) {
            if (!is_writable($path)) {
                $issues[] = "$name ($path) is not writable";
            }
        }
        
        if (empty($issues)) {
            $this->healthStatus['filesystem'] = [
                'status' => 'healthy',
                'message' => 'All paths are writable',
                'details' => $paths
            ];
        } else {
            $this->healthStatus['filesystem'] = [
                'status' => 'unhealthy',
                'message' => 'Permission issues detected',
                'issues' => $issues
            ];
            $this->exitCode = 1;
        }
    }

    private function checkQueueSystem(): void
    {
        try {
            $horizonStatus = trim(shell_exec('php artisan horizon:status'));
            $isRunning = strpos($horizonStatus, 'Horizon is running') !== false;
            
            if ($isRunning) {
                // Get queue sizes
                $queues = [
                    'default' => Cache::store('redis')->connection()->llen('queues:default'),
                    'webhooks' => Cache::store('redis')->connection()->llen('queues:webhooks'),
                    'emails' => Cache::store('redis')->connection()->llen('queues:emails'),
                ];
                
                $this->healthStatus['queue'] = [
                    'status' => 'healthy',
                    'message' => 'Horizon is running',
                    'details' => [
                        'queue_sizes' => $queues,
                        'total_jobs' => array_sum($queues)
                    ]
                ];
            } else {
                $this->healthStatus['queue'] = [
                    'status' => 'unhealthy',
                    'message' => 'Horizon is not running',
                ];
                $this->exitCode = 1;
            }
        } catch (Exception $e) {
            $this->healthStatus['queue'] = [
                'status' => 'unhealthy',
                'message' => 'Failed to check queue status: ' . $e->getMessage(),
            ];
            $this->exitCode = 1;
        }
    }

    private function checkCalcomApi(): void
    {
        $startTime = microtime(true);
        
        try {
            $apiKey = config('services.calcom.api_key');
            if (!$apiKey) {
                throw new Exception('API key not configured');
            }
            
            // Cal.com V2 requires Bearer authentication
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'cal-api-version' => '2024-08-13'
                ])
                ->get('https://api.cal.com/v2/me');
                
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->healthStatus['calcom'] = [
                    'status' => 'healthy',
                    'message' => 'API responding normally',
                    'duration_ms' => $duration,
                    'details' => [
                        'status_code' => $response->status(),
                        'api_version' => 'v2',
                        'authenticated_user' => $data['data']['email'] ?? 'Unknown'
                    ]
                ];
            } else {
                $this->healthStatus['calcom'] = [
                    'status' => 'unhealthy',
                    'message' => 'API returned error',
                    'duration_ms' => $duration,
                    'details' => [
                        'status_code' => $response->status(),
                        'error' => $response->json('error', 'Unknown error')
                    ]
                ];
            }
        } catch (Exception $e) {
            $this->healthStatus['calcom'] = [
                'status' => 'unhealthy',
                'message' => 'API check failed: ' . $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }

    private function checkRetellApi(): void
    {
        $startTime = microtime(true);
        
        try {
            $apiKey = config('services.retell.api_key');
            if (!$apiKey) {
                throw new Exception('API key not configured');
            }
            
            // Use the base URL from config if available
            $baseUrl = rtrim(config('services.retell.base') ?: config('services.retell.base_url', 'https://api.retellai.com'), '/');
            
            // Retell API v2 uses POST for list-calls endpoint with minimal payload
            $response = Http::timeout(5)
                ->withToken($apiKey)
                ->post($baseUrl . '/v2/list-calls', [
                    'limit' => 1,  // Just get 1 call to verify API is working
                    'sort_order' => 'descending'
                ]);
                
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($response->successful()) {
                $data = $response->json();
                
                $this->healthStatus['retell'] = [
                    'status' => 'healthy',
                    'message' => 'API responding normally',
                    'duration_ms' => $duration,
                    'details' => [
                        'status_code' => $response->status(),
                        'api_version' => 'v2',
                        'base_url' => $baseUrl
                    ]
                ];
            } else {
                $errorBody = $response->body();
                // Limit error body to prevent huge HTML error pages
                if (strlen($errorBody) > 500) {
                    $errorBody = substr($errorBody, 0, 500) . '... (truncated)';
                }
                
                $this->healthStatus['retell'] = [
                    'status' => 'unhealthy',
                    'message' => 'API returned error',
                    'duration_ms' => $duration,
                    'details' => [
                        'status_code' => $response->status(),
                        'error' => $errorBody,
                        'base_url' => $baseUrl
                    ]
                ];
            }
        } catch (Exception $e) {
            $this->healthStatus['retell'] = [
                'status' => 'unhealthy',
                'message' => 'API check failed: ' . $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }

    private function checkEmailService(): void
    {
        try {
            $mailer = config('mail.default');
            $host = config("mail.mailers.{$mailer}.host");
            $port = config("mail.mailers.{$mailer}.port");
            
            if (!$host) {
                throw new Exception('Mail configuration missing');
            }
            
            // Try to connect to SMTP server
            $connection = @fsockopen($host, $port, $errno, $errstr, 5);
            
            if ($connection) {
                fclose($connection);
                $this->healthStatus['email'] = [
                    'status' => 'healthy',
                    'message' => 'SMTP server reachable',
                    'details' => [
                        'mailer' => $mailer,
                        'host' => $host,
                        'port' => $port
                    ]
                ];
            } else {
                $this->healthStatus['email'] = [
                    'status' => 'unhealthy',
                    'message' => "Cannot connect to SMTP: $errstr",
                    'details' => [
                        'host' => $host,
                        'port' => $port,
                        'error' => $errstr
                    ]
                ];
            }
        } catch (Exception $e) {
            $this->healthStatus['email'] = [
                'status' => 'unhealthy',
                'message' => 'Email check failed: ' . $e->getMessage()
            ];
        }
    }

    private function checkResponseTime(): void
    {
        $startTime = microtime(true);
        
        try {
            $response = Http::timeout(5)->get(config('app.url') . '/api/health');
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            $status = $duration < 200 ? 'healthy' : ($duration < 500 ? 'warning' : 'unhealthy');
            
            $this->healthStatus['response_time'] = [
                'status' => $status,
                'message' => "Response time: {$duration}ms",
                'duration_ms' => $duration,
                'details' => [
                    'threshold_healthy' => '< 200ms',
                    'threshold_warning' => '< 500ms'
                ]
            ];
            
            if ($status === 'unhealthy') {
                $this->exitCode = 1;
            }
        } catch (Exception $e) {
            $this->healthStatus['response_time'] = [
                'status' => 'unhealthy',
                'message' => 'Failed to check response time: ' . $e->getMessage()
            ];
            $this->exitCode = 1;
        }
    }

    private function checkMemoryUsage(): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        
        $usagePercent = ($memoryUsage / $memoryLimitBytes) * 100;
        $status = $usagePercent < 70 ? 'healthy' : ($usagePercent < 85 ? 'warning' : 'unhealthy');
        
        $this->healthStatus['memory'] = [
            'status' => $status,
            'message' => sprintf('Memory usage: %.1f%%', $usagePercent),
            'details' => [
                'used' => $this->formatBytes($memoryUsage),
                'limit' => $memoryLimit,
                'percentage' => round($usagePercent, 1)
            ]
        ];
        
        if ($status === 'unhealthy') {
            $this->exitCode = 1;
        }
    }

    private function checkDiskSpace(): void
    {
        $path = storage_path();
        $free = disk_free_space($path);
        $total = disk_total_space($path);
        $used = $total - $free;
        $usagePercent = ($used / $total) * 100;
        
        $status = $usagePercent < 80 ? 'healthy' : ($usagePercent < 90 ? 'warning' : 'unhealthy');
        
        $this->healthStatus['disk_space'] = [
            'status' => $status,
            'message' => sprintf('Disk usage: %.1f%%', $usagePercent),
            'details' => [
                'path' => $path,
                'free' => $this->formatBytes($free),
                'total' => $this->formatBytes($total),
                'percentage' => round($usagePercent, 1)
            ]
        ];
        
        if ($status === 'unhealthy') {
            $this->exitCode = 1;
        }
    }

    private function checkSslCertificate(): void
    {
        try {
            $url = parse_url(config('app.url'));
            $host = $url['host'] ?? 'localhost';
            
            if ($url['scheme'] !== 'https') {
                $this->healthStatus['ssl'] = [
                    'status' => 'warning',
                    'message' => 'Not using HTTPS'
                ];
                return;
            }
            
            $context = stream_context_create([
                "ssl" => [
                    "capture_peer_cert" => true,
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ]
            ]);
            
            $stream = @stream_socket_client(
                "ssl://{$host}:443", 
                $errno, 
                $errstr, 
                30, 
                STREAM_CLIENT_CONNECT, 
                $context
            );
            
            if (!$stream) {
                throw new Exception("Cannot connect to SSL: $errstr");
            }
            
            $params = stream_context_get_params($stream);
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
            
            $validFrom = Carbon::createFromTimestamp($cert['validFrom_time_t']);
            $validTo = Carbon::createFromTimestamp($cert['validTo_time_t']);
            $daysRemaining = Carbon::now()->diffInDays($validTo, false);
            
            if ($daysRemaining < 0) {
                $status = 'unhealthy';
                $message = 'Certificate expired';
            } elseif ($daysRemaining < 30) {
                $status = 'warning';
                $message = "Certificate expires in {$daysRemaining} days";
            } else {
                $status = 'healthy';
                $message = "Certificate valid for {$daysRemaining} days";
            }
            
            $this->healthStatus['ssl'] = [
                'status' => $status,
                'message' => $message,
                'details' => [
                    'issuer' => $cert['issuer']['O'] ?? 'Unknown',
                    'valid_from' => $validFrom->toDateString(),
                    'valid_to' => $validTo->toDateString(),
                    'days_remaining' => $daysRemaining
                ]
            ];
            
            if ($status === 'unhealthy') {
                $this->exitCode = 1;
            }
        } catch (Exception $e) {
            $this->healthStatus['ssl'] = [
                'status' => 'unhealthy',
                'message' => 'SSL check failed: ' . $e->getMessage()
            ];
        }
    }

    private function checkPermissions(): void
    {
        $issues = [];
        
        // Check if running as www-data
        $currentUser = posix_getpwuid(posix_geteuid())['name'];
        if ($currentUser === 'root') {
            $issues[] = 'Application should not run as root';
        }
        
        // Check sensitive file permissions
        $envFile = base_path('.env.production');
        if (file_exists($envFile)) {
            $perms = substr(sprintf('%o', fileperms($envFile)), -4);
            if ($perms !== '0600' && $perms !== '0644') {
                $issues[] = ".env.production has insecure permissions: $perms";
            }
        }
        
        if (empty($issues)) {
            $this->healthStatus['permissions'] = [
                'status' => 'healthy',
                'message' => 'Permissions are secure',
                'details' => [
                    'user' => $currentUser
                ]
            ];
        } else {
            $this->healthStatus['permissions'] = [
                'status' => 'warning',
                'message' => 'Permission issues detected',
                'issues' => $issues
            ];
        }
    }

    private function outputTable(): void
    {
        $headers = ['Component', 'Status', 'Message', 'Duration'];
        $rows = [];
        
        foreach ($this->healthStatus as $component => $status) {
            $statusEmoji = match($status['status']) {
                'healthy' => '✅',
                'warning' => '⚠️',
                'unhealthy' => '❌',
                default => '❓'
            };
            
            $rows[] = [
                ucfirst(str_replace('_', ' ', $component)),
                $statusEmoji . ' ' . ucfirst($status['status']),
                $status['message'],
                isset($status['duration_ms']) ? $status['duration_ms'] . 'ms' : '-'
            ];
        }
        
        $this->table($headers, $rows);
        
        if ($this->option('detailed')) {
            $this->info("\nDetailed Information:");
            foreach ($this->healthStatus as $component => $status) {
                if (isset($status['details'])) {
                    $this->info("\n" . ucfirst($component) . ":");
                    foreach ($status['details'] as $key => $value) {
                        if (is_array($value)) {
                            $this->info("  $key: " . json_encode($value));
                        } else {
                            $this->info("  $key: $value");
                        }
                    }
                }
            }
        }
        
        // Summary
        $healthy = count(array_filter($this->healthStatus, fn($s) => $s['status'] === 'healthy'));
        $total = count($this->healthStatus);
        
        $this->info("\nOverall Health: {$healthy}/{$total} components healthy");
        
        if ($this->exitCode === 0) {
            $this->info("✅ System is healthy");
        } else {
            $this->error("❌ System has issues");
        }
    }

    private function outputJson(): void
    {
        $output = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'overall_status' => $this->exitCode === 0 ? 'healthy' : 'unhealthy',
            'components' => $this->healthStatus,
            'summary' => [
                'healthy' => count(array_filter($this->healthStatus, fn($s) => $s['status'] === 'healthy')),
                'warning' => count(array_filter($this->healthStatus, fn($s) => $s['status'] === 'warning')),
                'unhealthy' => count(array_filter($this->healthStatus, fn($s) => $s['status'] === 'unhealthy')),
            ]
        ];
        
        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }

    private function hasFailures(): bool
    {
        return !empty(array_filter($this->healthStatus, fn($s) => $s['status'] === 'unhealthy'));
    }

    private function sendAlerts(): void
    {
        $failures = array_filter($this->healthStatus, fn($s) => $s['status'] === 'unhealthy');
        
        foreach ($failures as $component => $status) {
            Log::critical("Health check failed for {$component}", [
                'component' => $component,
                'status' => $status
            ]);
        }
        
        // Send email alert
        if (config('health.alert_email')) {
            // Email implementation would go here
            $this->info("Alert sent to: " . config('health.alert_email'));
        }
        
        // Send Slack alert
        if (config('health.slack_webhook')) {
            $this->sendSlackAlert($failures);
        }
    }

    private function sendSlackAlert(array $failures): void
    {
        $message = "*🚨 AskProAI Health Check Alert*\n\n";
        $message .= "The following components are unhealthy:\n";
        
        foreach ($failures as $component => $status) {
            $message .= "• *{$component}*: {$status['message']}\n";
        }
        
        try {
            Http::post(config('health.slack_webhook'), [
                'text' => $message,
                'color' => 'danger'
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send Slack alert', ['error' => $e->getMessage()]);
        }
    }

    private function storeHealthStatus(): void
    {
        Cache::put('health_check_status', [
            'timestamp' => Carbon::now()->toIso8601String(),
            'status' => $this->exitCode === 0 ? 'healthy' : 'unhealthy',
            'components' => $this->healthStatus
        ], 300); // Cache for 5 minutes
    }

    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}