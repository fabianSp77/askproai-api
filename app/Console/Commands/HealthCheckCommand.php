<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:health-check
                            {--deep : Run deep analysis including performance metrics}
                            {--json : Output results as JSON}
                            {--summary : Only show errors and warnings}
                            {--alert : Send alerts if critical issues found}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform comprehensive health check on all system components';

    /**
     * Health check results
     */
    protected $results = [];
    protected $hasErrors = false;
    protected $hasWarnings = false;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deep = $this->option('deep');
        $json = $this->option('json');
        $summary = $this->option('summary');
        $alert = $this->option('alert');

        if (!$json && !$summary) {
            $this->info('🏥 API Gateway Health Check');
            $this->info('═══════════════════════════════════════════');
            $this->info('Starting system diagnostics at ' . Carbon::now()->format('Y-m-d H:i:s'));
            $this->newLine();
        }

        // Run all health checks
        $this->checkDatabase();
        $this->checkCache();
        $this->checkQueue();
        $this->checkFileSystem();
        $this->checkWebServer();
        $this->checkServices();
        $this->checkIntegrations();

        if ($deep) {
            $this->checkPerformance();
            $this->checkSecurity();
            $this->checkLogs();
        }

        // Output results
        if ($json) {
            $this->outputJson();
        } else {
            $this->outputTable();
        }

        // Send alerts if needed
        if ($alert && ($this->hasErrors || $this->hasWarnings)) {
            $this->sendAlerts();
        }

        // Return appropriate exit code
        if ($this->hasErrors) {
            return Command::FAILURE;
        } elseif ($this->hasWarnings) {
            return 2; // Custom exit code for warnings
        }

        return Command::SUCCESS;
    }

    /**
     * Check database connectivity and status
     */
    protected function checkDatabase()
    {
        $component = 'Database';

        try {
            // Test connection
            DB::connection()->getPdo();

            // Check table count
            $tables = DB::select('SHOW TABLES');
            $tableCount = count($tables);

            // Check key tables exist
            $requiredTables = ['users', 'customers', 'companies', 'calls', 'appointments'];
            $missingTables = [];

            foreach ($requiredTables as $table) {
                if (!DB::getSchemaBuilder()->hasTable($table)) {
                    $missingTables[] = $table;
                }
            }

            if (!empty($missingTables)) {
                $this->addResult($component, 'ERROR', 'Missing tables: ' . implode(', ', $missingTables));
                return;
            }

            // Check database size
            $dbName = config('database.connections.mysql.database');
            $sizeQuery = DB::select("
                SELECT
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
                FROM information_schema.tables
                WHERE table_schema = ?
            ", [$dbName]);

            $sizeMb = $sizeQuery[0]->size_mb ?? 0;

            // Check slow queries (if available)
            $slowQueries = 0;
            try {
                $slowQueryResult = DB::select("SHOW GLOBAL STATUS LIKE 'Slow_queries'");
                $slowQueries = $slowQueryResult[0]->Value ?? 0;
            } catch (\Exception $e) {
                // Ignore if can't access global status
            }

            $status = 'OK';
            $details = "{$tableCount} tables, {$sizeMb} MB";

            if ($sizeMb > 1000) {
                $status = 'WARNING';
                $details .= ' (Large database)';
            }

            if ($slowQueries > 100) {
                $status = 'WARNING';
                $details .= ", {$slowQueries} slow queries";
            }

            $this->addResult($component, $status, $details);

        } catch (\Exception $e) {
            $this->addResult($component, 'ERROR', 'Connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Check cache system
     */
    protected function checkCache()
    {
        $component = 'Cache';

        try {
            // Test cache write
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 60);

            // Test cache read
            $value = Cache::get($testKey);

            if ($value !== 'test') {
                throw new \Exception('Cache read/write mismatch');
            }

            // Clean up
            Cache::forget($testKey);

            // Check cache driver
            $driver = config('cache.default');

            // Get cache stats if Redis
            $stats = '';
            if ($driver === 'redis') {
                try {
                    $redis = Cache::getRedis();
                    $info = $redis->info();
                    $memory = round($info['used_memory'] / 1024 / 1024, 2);
                    $stats = ", Memory: {$memory} MB";
                } catch (\Exception $e) {
                    // Ignore Redis stats errors
                }
            }

            $this->addResult($component, 'OK', "Driver: {$driver}{$stats}");

        } catch (\Exception $e) {
            $this->addResult($component, 'ERROR', 'Cache system failed: ' . $e->getMessage());
        }
    }

    /**
     * Check queue system
     */
    protected function checkQueue()
    {
        $component = 'Queue';

        try {
            $driver = config('queue.default');

            if ($driver === 'sync') {
                $this->addResult($component, 'WARNING', 'Using sync driver (no async processing)');
                return;
            }

            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')->count();

            // Check queue size (approximate for database driver)
            $queueSize = 0;
            if ($driver === 'database') {
                $queueSize = DB::table('jobs')->count();
            }

            $status = 'OK';
            $details = "Driver: {$driver}, Queue: {$queueSize} jobs";

            if ($failedJobs > 0) {
                $status = 'WARNING';
                $details .= ", Failed: {$failedJobs}";
            }

            if ($queueSize > 1000) {
                $status = 'WARNING';
                $details .= ' (High queue depth)';
            }

            if ($failedJobs > 100) {
                $status = 'ERROR';
            }

            $this->addResult($component, $status, $details);

        } catch (\Exception $e) {
            $this->addResult($component, 'ERROR', 'Queue check failed: ' . $e->getMessage());
        }
    }

    /**
     * Check file system and disk space
     */
    protected function checkFileSystem()
    {
        $component = 'Disk Space';

        try {
            $paths = [
                '/' => 'System',
                '/var/www' => 'Application',
                '/var/www/backups' => 'Backups',
            ];

            $warnings = [];
            $errors = [];

            foreach ($paths as $path => $label) {
                if (!is_dir($path)) {
                    continue;
                }

                $total = disk_total_space($path);
                $free = disk_free_space($path);
                $used = $total - $free;
                $percentUsed = round(($used / $total) * 100, 1);
                $freeGb = round($free / 1024 / 1024 / 1024, 2);

                if ($percentUsed > 90) {
                    $errors[] = "{$label}: {$percentUsed}% used";
                } elseif ($percentUsed > 80) {
                    $warnings[] = "{$label}: {$percentUsed}% used";
                }

                // Check if backup directory exists and is writable
                if ($path === '/var/www/backups') {
                    if (!is_writable($path)) {
                        $errors[] = 'Backup directory not writable';
                    }
                }
            }

            // Check storage directory permissions
            $storagePath = base_path('storage');
            if (!is_writable($storagePath)) {
                $errors[] = 'Storage directory not writable';
            }

            if (!empty($errors)) {
                $this->addResult($component, 'ERROR', implode(', ', $errors));
            } elseif (!empty($warnings)) {
                $this->addResult($component, 'WARNING', implode(', ', $warnings));
            } else {
                $systemFree = round(disk_free_space('/') / 1024 / 1024 / 1024, 2);
                $this->addResult($component, 'OK', "{$systemFree} GB free on system");
            }

        } catch (\Exception $e) {
            $this->addResult($component, 'ERROR', 'Disk check failed: ' . $e->getMessage());
        }
    }

    /**
     * Check web server status
     */
    protected function checkWebServer()
    {
        $component = 'Web Server';

        try {
            // Check if site is accessible
            $appUrl = config('app.url');

            $response = Http::timeout(5)->get($appUrl . '/health');

            if ($response->successful()) {
                $responseTime = round($response->handlerStats()['total_time'] ?? 0, 3);

                $status = 'OK';
                $details = "Response time: {$responseTime}s";

                if ($responseTime > 3) {
                    $status = 'WARNING';
                    $details .= ' (Slow response)';
                }

                $this->addResult($component, $status, $details);
            } else {
                $this->addResult($component, 'ERROR', 'Health endpoint returned ' . $response->status());
            }

        } catch (\Exception $e) {
            $this->addResult($component, 'WARNING', 'Could not check web server: ' . $e->getMessage());
        }
    }

    /**
     * Check critical services
     */
    protected function checkServices()
    {
        $component = 'Services';

        $services = [
            'nginx' => 'Web Server',
            'php8.3-fpm' => 'PHP-FPM',
            'mysql' => 'Database',
            'supervisor' => 'Process Manager',
        ];

        $statuses = [];
        $hasIssues = false;

        foreach ($services as $service => $name) {
            exec("systemctl is-active {$service} 2>/dev/null", $output, $returnCode);

            if ($returnCode === 0) {
                $statuses[] = "{$name}: ✓";
            } else {
                $statuses[] = "{$name}: ✗";
                $hasIssues = true;
            }
        }

        if ($hasIssues) {
            $this->addResult($component, 'ERROR', 'Some services are down: ' . implode(', ', $statuses));
        } else {
            $this->addResult($component, 'OK', 'All services running');
        }
    }

    /**
     * Check external integrations
     */
    protected function checkIntegrations()
    {
        $component = 'Integrations';

        $issues = [];

        // Check Cal.com
        if (config('services.calcom.api_key')) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders(['Authorization' => 'Bearer ' . config('services.calcom.api_key')])
                    ->get(config('services.calcom.base_url') . '/users/me');

                if (!$response->successful()) {
                    $issues[] = 'Cal.com API error';
                }
            } catch (\Exception $e) {
                $issues[] = 'Cal.com unreachable';
            }
        }

        // Check Retell
        if (config('services.retellai.api_key')) {
            try {
                // Simple connectivity check
                $response = Http::timeout(5)
                    ->withHeaders(['Authorization' => 'Bearer ' . config('services.retellai.api_key')])
                    ->get('https://api.retell.ai/v1/agent');

                if ($response->status() === 401) {
                    $issues[] = 'Retell API key invalid';
                }
            } catch (\Exception $e) {
                $issues[] = 'Retell unreachable';
            }
        }

        if (empty($issues)) {
            $this->addResult($component, 'OK', 'All integrations operational');
        } else {
            $this->addResult($component, 'WARNING', implode(', ', $issues));
        }
    }

    /**
     * Check performance metrics (deep mode)
     */
    protected function checkPerformance()
    {
        $component = 'Performance';

        try {
            // Check response times for key queries
            $start = microtime(true);
            DB::table('customers')->limit(100)->get();
            $queryTime = round((microtime(true) - $start) * 1000, 2);

            // Check memory usage
            $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
            $memoryLimit = ini_get('memory_limit');

            // Check CPU load
            $load = sys_getloadavg();
            $cpuCount = shell_exec('nproc');
            $loadPerCpu = round($load[0] / $cpuCount, 2);

            $status = 'OK';
            $details = "Query: {$queryTime}ms, Memory: {$memoryUsage}MB/{$memoryLimit}, Load: {$loadPerCpu}";

            if ($queryTime > 100) {
                $status = 'WARNING';
                $details .= ' (Slow queries)';
            }

            if ($loadPerCpu > 2) {
                $status = 'WARNING';
                $details .= ' (High CPU load)';
            }

            $this->addResult($component, $status, $details);

        } catch (\Exception $e) {
            $this->addResult($component, 'ERROR', 'Performance check failed: ' . $e->getMessage());
        }
    }

    /**
     * Check security status (deep mode)
     */
    protected function checkSecurity()
    {
        $component = 'Security';

        $issues = [];

        // Check debug mode
        if (config('app.debug') === true && config('app.env') === 'production') {
            $issues[] = 'Debug mode enabled in production';
        }

        // Check HTTPS
        if (!request()->secure() && config('app.env') === 'production') {
            $issues[] = 'HTTPS not enforced';
        }

        // Check .env file permissions
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $perms = substr(sprintf('%o', fileperms($envPath)), -4);
            if ($perms !== '0600' && $perms !== '0644') {
                $issues[] = '.env file has loose permissions';
            }
        }

        // Check for default passwords
        if (config('database.connections.mysql.password') === '' ||
            config('database.connections.mysql.password') === 'password') {
            $issues[] = 'Default database password detected';
        }

        if (empty($issues)) {
            $this->addResult($component, 'OK', 'No security issues detected');
        } else {
            $this->addResult($component, 'WARNING', implode(', ', $issues));
        }
    }

    /**
     * Check logs for errors (deep mode)
     */
    protected function checkLogs()
    {
        $component = 'Error Logs';

        try {
            $logPath = storage_path('logs/laravel.log');

            if (!file_exists($logPath)) {
                $this->addResult($component, 'OK', 'No log file found');
                return;
            }

            // Check last 100 lines for errors
            $lines = shell_exec("tail -100 {$logPath} | grep -i error | wc -l");
            $errorCount = intval(trim($lines));

            // Check log file size
            $sizeMb = round(filesize($logPath) / 1024 / 1024, 2);

            $status = 'OK';
            $details = "Size: {$sizeMb}MB";

            if ($errorCount > 0) {
                $status = 'WARNING';
                $details .= ", Recent errors: {$errorCount}";
            }

            if ($sizeMb > 100) {
                $status = 'WARNING';
                $details .= ' (Large log file)';
            }

            $this->addResult($component, $status, $details);

        } catch (\Exception $e) {
            $this->addResult($component, 'ERROR', 'Log check failed: ' . $e->getMessage());
        }
    }

    /**
     * Add result to collection
     */
    protected function addResult($component, $status, $details)
    {
        $this->results[] = [
            'component' => $component,
            'status' => $status,
            'details' => $details,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];

        if ($status === 'ERROR') {
            $this->hasErrors = true;
        } elseif ($status === 'WARNING') {
            $this->hasWarnings = true;
        }

        // Log critical issues
        if ($status === 'ERROR') {
            Log::error("Health check failed for {$component}", [
                'details' => $details
            ]);
        }
    }

    /**
     * Output results as table
     */
    protected function outputTable()
    {
        $summary = $this->option('summary');

        if (!$summary) {
            $this->table(
                ['Component', 'Status', 'Details'],
                array_map(function($result) {
                    $status = $result['status'];
                    if ($status === 'OK') {
                        $status = '<fg=green>✓ OK</>';
                    } elseif ($status === 'WARNING') {
                        $status = '<fg=yellow>⚠ WARNING</>';
                    } elseif ($status === 'ERROR') {
                        $status = '<fg=red>✗ ERROR</>';
                    }

                    return [
                        $result['component'],
                        $status,
                        $result['details']
                    ];
                }, $this->results)
            );
        } else {
            // In summary mode, only show warnings and errors
            foreach ($this->results as $result) {
                if ($result['status'] !== 'OK') {
                    $this->line("{$result['component']}: {$result['status']} - {$result['details']}");
                }
            }
        }

        // Summary
        if (!$summary) {
            $this->newLine();
            $totalChecks = count($this->results);
            $okCount = count(array_filter($this->results, fn($r) => $r['status'] === 'OK'));
            $warningCount = count(array_filter($this->results, fn($r) => $r['status'] === 'WARNING'));
            $errorCount = count(array_filter($this->results, fn($r) => $r['status'] === 'ERROR'));

            if ($this->hasErrors) {
                $this->error("❌ Health check completed with errors: {$errorCount} errors, {$warningCount} warnings");
            } elseif ($this->hasWarnings) {
                $this->warn("⚠️  Health check completed with warnings: {$warningCount} warnings");
            } else {
                $this->info("✅ Health check passed! All {$totalChecks} components are healthy.");
            }
        }
    }

    /**
     * Output results as JSON
     */
    protected function outputJson()
    {
        $output = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'overall_status' => $this->hasErrors ? 'ERROR' : ($this->hasWarnings ? 'WARNING' : 'OK'),
            'summary' => [
                'total' => count($this->results),
                'ok' => count(array_filter($this->results, fn($r) => $r['status'] === 'OK')),
                'warning' => count(array_filter($this->results, fn($r) => $r['status'] === 'WARNING')),
                'error' => count(array_filter($this->results, fn($r) => $r['status'] === 'ERROR')),
            ],
            'results' => $this->results
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }

    /**
     * Send alerts for critical issues
     */
    protected function sendAlerts()
    {
        // Log critical alert
        Log::critical('Health check alert triggered', [
            'errors' => array_filter($this->results, fn($r) => $r['status'] === 'ERROR'),
            'warnings' => array_filter($this->results, fn($r) => $r['status'] === 'WARNING'),
        ]);

        // TODO: Implement email/SMS alerts when notification system is ready
    }
}