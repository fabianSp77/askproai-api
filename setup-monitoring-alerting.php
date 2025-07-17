<?php
/**
 * Monitoring & Alerting Setup Script
 * Konfiguriert Monitoring nach Emergency Fix
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

echo "📊 Setting up Monitoring & Alerting\n";
echo "==================================\n\n";

// 1. Create monitoring configuration
echo "1. Creating monitoring configuration:\n";
echo "------------------------------------\n";

$monitoringConfig = [
    'metrics' => [
        'response_time' => [
            'warning' => 500, // ms
            'critical' => 1000 // ms
        ],
        'error_rate' => [
            'warning' => 0.01, // 1%
            'critical' => 0.05 // 5%
        ],
        'queue_size' => [
            'warning' => 1000,
            'critical' => 5000
        ],
        'memory_usage' => [
            'warning' => 80, // %
            'critical' => 90 // %
        ]
    ],
    'alerts' => [
        'channels' => ['log', 'mail', 'slack'],
        'recipients' => [
            'email' => ['dev@askproai.de'],
            'slack' => ['#alerts']
        ]
    ]
];

$configPath = config_path('monitoring.php');
$configContent = "<?php\n\nreturn " . var_export($monitoringConfig, true) . ";\n";

try {
    File::put($configPath, $configContent);
    echo "   ✅ Created config/monitoring.php\n";
} catch (\Exception $e) {
    echo "   ❌ Error creating config: " . $e->getMessage() . "\n";
}

// 2. Create health check endpoint
echo "\n2. Creating health check endpoint:\n";
echo "----------------------------------\n";

$healthCheckController = <<<'PHP'
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class HealthCheckController extends Controller
{
    public function __invoke(Request $request)
    {
        $checks = [];
        
        // Database check
        try {
            DB::select('SELECT 1');
            $checks['database'] = ['status' => 'ok', 'message' => 'Connected'];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        // Cache check
        try {
            Cache::put('health_check', time(), 10);
            $value = Cache::get('health_check');
            Cache::forget('health_check');
            $checks['cache'] = ['status' => 'ok', 'message' => 'Working'];
        } catch (\Exception $e) {
            $checks['cache'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        // Queue check
        try {
            $queueSize = Queue::size();
            $checks['queue'] = [
                'status' => $queueSize < 1000 ? 'ok' : 'warning',
                'message' => "Size: $queueSize"
            ];
        } catch (\Exception $e) {
            $checks['queue'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        // Overall status
        $hasError = collect($checks)->contains('status', 'error');
        $status = $hasError ? 503 : 200;
        
        return response()->json([
            'status' => $hasError ? 'error' : 'ok',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks
        ], $status);
    }
}
PHP;

try {
    File::put(app_path('Http/Controllers/HealthCheckController.php'), $healthCheckController);
    echo "   ✅ Created HealthCheckController\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 3. Create monitoring middleware
echo "\n3. Creating monitoring middleware:\n";
echo "----------------------------------\n";

$monitoringMiddleware = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MonitoringMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $response = $next($request);
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $memoryUsed = memory_get_usage() - $startMemory;
        
        // Log slow requests
        if ($duration > config('monitoring.metrics.response_time.warning', 500)) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => $duration,
                'memory_bytes' => $memoryUsed,
                'user_id' => auth()->id()
            ]);
        }
        
        // Track metrics
        $this->trackMetrics($request, $response, $duration);
        
        // Add timing header
        $response->header('X-Response-Time', $duration . 'ms');
        
        return $response;
    }
    
    private function trackMetrics($request, $response, $duration)
    {
        $key = 'metrics:' . now()->format('Y-m-d:H');
        
        Cache::increment($key . ':requests');
        Cache::increment($key . ':response_time', $duration);
        
        if ($response->status() >= 400) {
            Cache::increment($key . ':errors');
        }
        
        // Store for 48 hours
        Cache::put($key . ':ttl', now()->addHours(48), 48 * 60);
    }
}
PHP;

try {
    File::put(app_path('Http/Middleware/MonitoringMiddleware.php'), $monitoringMiddleware);
    echo "   ✅ Created MonitoringMiddleware\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 4. Create alert service
echo "\n4. Creating alert service:\n";
echo "--------------------------\n";

$alertService = <<<'PHP'
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class AlertService
{
    public function sendAlert(string $level, string $message, array $context = [])
    {
        // Always log
        Log::channel('alerts')->$level($message, $context);
        
        // Send based on level
        switch ($level) {
            case 'critical':
            case 'emergency':
                $this->sendEmail($level, $message, $context);
                $this->sendSlack($level, $message, $context);
                break;
            case 'error':
            case 'warning':
                $this->sendSlack($level, $message, $context);
                break;
        }
    }
    
    private function sendEmail($level, $message, $context)
    {
        $recipients = config('monitoring.alerts.recipients.email', []);
        
        foreach ($recipients as $email) {
            try {
                Mail::raw($this->formatMessage($level, $message, $context), function ($mail) use ($email, $level) {
                    $mail->to($email)
                         ->subject("[{$level}] AskProAI Alert");
                });
            } catch (\Exception $e) {
                Log::error('Failed to send alert email', ['error' => $e->getMessage()]);
            }
        }
    }
    
    private function sendSlack($level, $message, $context)
    {
        $webhook = env('SLACK_WEBHOOK_URL');
        if (!$webhook) return;
        
        $color = match($level) {
            'critical', 'emergency' => 'danger',
            'error' => 'warning',
            default => 'good'
        };
        
        try {
            Http::post($webhook, [
                'attachments' => [[
                    'color' => $color,
                    'title' => strtoupper($level) . ': ' . $message,
                    'text' => json_encode($context, JSON_PRETTY_PRINT),
                    'footer' => 'AskProAI Monitoring',
                    'ts' => time()
                ]]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Slack alert', ['error' => $e->getMessage()]);
        }
    }
    
    private function formatMessage($level, $message, $context)
    {
        $formatted = "Alert Level: " . strtoupper($level) . "\n";
        $formatted .= "Message: $message\n";
        $formatted .= "Time: " . now()->toDateTimeString() . "\n";
        $formatted .= "Server: " . gethostname() . "\n\n";
        
        if (!empty($context)) {
            $formatted .= "Context:\n" . json_encode($context, JSON_PRETTY_PRINT);
        }
        
        return $formatted;
    }
}
PHP;

try {
    File::put(app_path('Services/AlertService.php'), $alertService);
    echo "   ✅ Created AlertService\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 5. Create monitoring command
echo "\n5. Creating monitoring command:\n";
echo "-------------------------------\n";

$monitoringCommand = <<<'PHP'
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AlertService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MonitorSystemCommand extends Command
{
    protected $signature = 'monitor:check';
    protected $description = 'Check system health and send alerts';
    
    private AlertService $alertService;
    
    public function __construct(AlertService $alertService)
    {
        parent::__construct();
        $this->alertService = $alertService;
    }
    
    public function handle()
    {
        $this->info('Running system health checks...');
        
        // Check response times
        $this->checkResponseTimes();
        
        // Check error rates
        $this->checkErrorRates();
        
        // Check queue sizes
        $this->checkQueueSizes();
        
        // Check disk space
        $this->checkDiskSpace();
        
        // Check memory usage
        $this->checkMemoryUsage();
        
        $this->info('Health checks completed.');
    }
    
    private function checkResponseTimes()
    {
        $avgResponseTime = Cache::get('metrics:' . now()->format('Y-m-d:H') . ':response_time', 0) / 
                          max(Cache::get('metrics:' . now()->format('Y-m-d:H') . ':requests', 1), 1);
        
        if ($avgResponseTime > config('monitoring.metrics.response_time.critical')) {
            $this->alertService->sendAlert('critical', 'High response time detected', [
                'average_ms' => round($avgResponseTime, 2)
            ]);
        } elseif ($avgResponseTime > config('monitoring.metrics.response_time.warning')) {
            $this->alertService->sendAlert('warning', 'Elevated response time', [
                'average_ms' => round($avgResponseTime, 2)
            ]);
        }
    }
    
    private function checkErrorRates()
    {
        $requests = Cache::get('metrics:' . now()->format('Y-m-d:H') . ':requests', 1);
        $errors = Cache::get('metrics:' . now()->format('Y-m-d:H') . ':errors', 0);
        $errorRate = $errors / max($requests, 1);
        
        if ($errorRate > config('monitoring.metrics.error_rate.critical')) {
            $this->alertService->sendAlert('critical', 'High error rate detected', [
                'error_rate' => round($errorRate * 100, 2) . '%',
                'errors' => $errors,
                'requests' => $requests
            ]);
        }
    }
    
    private function checkQueueSizes()
    {
        $queues = ['default', 'webhooks', 'emails'];
        
        foreach ($queues as $queue) {
            $size = DB::table('jobs')->where('queue', $queue)->count();
            
            if ($size > config('monitoring.metrics.queue_size.critical')) {
                $this->alertService->sendAlert('critical', 'Queue backlog critical', [
                    'queue' => $queue,
                    'size' => $size
                ]);
            }
        }
    }
    
    private function checkDiskSpace()
    {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        $usedPercent = (($total - $free) / $total) * 100;
        
        if ($usedPercent > 90) {
            $this->alertService->sendAlert('critical', 'Disk space critical', [
                'used_percent' => round($usedPercent, 2),
                'free_gb' => round($free / 1073741824, 2)
            ]);
        }
    }
    
    private function checkMemoryUsage()
    {
        $memInfo = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatch);
        preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availMatch);
        
        if (isset($totalMatch[1]) && isset($availMatch[1])) {
            $usedPercent = (($totalMatch[1] - $availMatch[1]) / $totalMatch[1]) * 100;
            
            if ($usedPercent > config('monitoring.metrics.memory_usage.critical')) {
                $this->alertService->sendAlert('critical', 'Memory usage critical', [
                    'used_percent' => round($usedPercent, 2)
                ]);
            }
        }
    }
}
PHP;

try {
    File::put(app_path('Console/Commands/MonitorSystemCommand.php'), $monitoringCommand);
    echo "   ✅ Created MonitorSystemCommand\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 6. Set up cron jobs
echo "\n6. Cron job configuration:\n";
echo "--------------------------\n";

$cronJobs = [
    "* * * * * cd /var/www/api-gateway && php artisan schedule:run >> /dev/null 2>&1",
    "*/5 * * * * cd /var/www/api-gateway && php artisan monitor:check >> /dev/null 2>&1",
    "0 * * * * cd /var/www/api-gateway && php artisan horizon:snapshot >> /dev/null 2>&1",
    "0 2 * * * cd /var/www/api-gateway && php artisan askproai:backup --type=full >> /dev/null 2>&1"
];

echo "Add these to your crontab:\n";
foreach ($cronJobs as $job) {
    echo "   $job\n";
}

// 7. Create alerts log channel
echo "\n7. Creating alerts log channel:\n";
echo "-------------------------------\n";

$loggingConfig = config('logging');
$loggingConfig['channels']['alerts'] = [
    'driver' => 'daily',
    'path' => storage_path('logs/alerts.log'),
    'level' => 'warning',
    'days' => 30,
];

echo "   ℹ️ Add 'alerts' channel to config/logging.php\n";

echo "\n==================================\n";
echo "✅ Monitoring setup completed\n";
echo "\nNext steps:\n";
echo "1. Add routes for health check: Route::get('/health', HealthCheckController::class)\n";
echo "2. Register MonitoringMiddleware in app/Http/Kernel.php\n";
echo "3. Configure SLACK_WEBHOOK_URL in .env\n";
echo "4. Set up external monitoring (UptimeRobot, Pingdom)\n";
echo "5. Configure log aggregation (ELK Stack, Datadog)\n";