<?php

namespace App\Services\Deployment;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SafeDeploymentService
{
    protected array $config;
    protected array $preDeploymentChecks = [];
    protected array $rollbackData = [];
    protected string $deploymentId;
    
    public function __construct()
    {
        $this->config = config('deployment', [
            'checks' => [
                'database' => true,
                'migrations' => true,
                'tests' => true,
                'dependencies' => true,
                'disk_space' => true,
                'services' => true
            ],
            'rollback' => [
                'auto_rollback' => true,
                'max_downtime' => 300, // 5 minutes
                'backup_retention' => 7 // days
            ],
            'notifications' => [
                'slack' => env('DEPLOYMENT_SLACK_WEBHOOK'),
                'email' => env('DEPLOYMENT_EMAIL')
            ],
            'zero_downtime' => [
                'enabled' => true,
                'strategy' => 'blue_green', // or 'rolling'
                'health_check_url' => '/health',
                'warm_up_time' => 30 // seconds
            ]
        ]);
        
        $this->deploymentId = Str::uuid()->toString();
    }
    
    /**
     * Execute safe deployment
     */
    public function deploy(array $options = []): array
    {
        $startTime = microtime(true);
        $result = [
            'deployment_id' => $this->deploymentId,
            'started_at' => now()->toIso8601String(),
            'status' => 'in_progress',
            'steps' => []
        ];
        
        try {
            // Step 1: Pre-deployment checks
            $this->log('Starting pre-deployment checks');
            $checksResult = $this->runPreDeploymentChecks();
            $result['steps']['pre_checks'] = $checksResult;
            
            if (!$checksResult['passed']) {
                throw new \Exception('Pre-deployment checks failed: ' . json_encode($checksResult['failures']));
            }
            
            // Step 2: Create backup point
            $this->log('Creating backup point');
            $backupResult = $this->createBackupPoint();
            $result['steps']['backup'] = $backupResult;
            
            // Step 3: Enable maintenance mode (if not zero-downtime)
            if (!$this->config['zero_downtime']['enabled']) {
                $this->log('Enabling maintenance mode');
                Artisan::call('down', [
                    '--message' => 'System upgrade in progress',
                    '--retry' => 60
                ]);
                $result['steps']['maintenance_mode'] = ['enabled' => true];
            }
            
            // Step 4: Deploy new code
            $this->log('Deploying new code');
            $deployResult = $this->deployCode($options);
            $result['steps']['code_deployment'] = $deployResult;
            
            // Step 5: Run migrations
            $this->log('Running database migrations');
            $migrationResult = $this->runMigrations();
            $result['steps']['migrations'] = $migrationResult;
            
            // Step 6: Clear caches
            $this->log('Clearing caches');
            $cacheResult = $this->clearCaches();
            $result['steps']['cache_clear'] = $cacheResult;
            
            // Step 7: Run post-deployment tests
            $this->log('Running post-deployment tests');
            $testResult = $this->runPostDeploymentTests();
            $result['steps']['post_tests'] = $testResult;
            
            if (!$testResult['passed']) {
                throw new \Exception('Post-deployment tests failed');
            }
            
            // Step 8: Warm up application
            if ($this->config['zero_downtime']['enabled']) {
                $this->log('Warming up application');
                $warmupResult = $this->warmUpApplication();
                $result['steps']['warmup'] = $warmupResult;
            }
            
            // Step 9: Switch traffic (zero-downtime)
            if ($this->config['zero_downtime']['enabled']) {
                $this->log('Switching traffic to new version');
                $switchResult = $this->switchTraffic();
                $result['steps']['traffic_switch'] = $switchResult;
            }
            
            // Step 10: Disable maintenance mode
            if (!$this->config['zero_downtime']['enabled']) {
                $this->log('Disabling maintenance mode');
                Artisan::call('up');
                $result['steps']['maintenance_mode'] = ['enabled' => false];
            }
            
            // Step 11: Health check
            $this->log('Running health checks');
            $healthResult = $this->runHealthCheck();
            $result['steps']['health_check'] = $healthResult;
            
            if (!$healthResult['healthy']) {
                throw new \Exception('Health check failed after deployment');
            }
            
            // Step 12: Monitor for issues
            $this->log('Monitoring for issues');
            $monitorResult = $this->monitorDeployment();
            $result['steps']['monitoring'] = $monitorResult;
            
            // Mark deployment as successful
            $result['status'] = 'success';
            $result['completed_at'] = now()->toIso8601String();
            $result['duration'] = round(microtime(true) - $startTime, 2);
            
            // Send success notification
            $this->sendNotification('Deployment successful', $result);
            
            // Clean up old backups
            $this->cleanupOldBackups();
            
        } catch (\Exception $e) {
            $result['status'] = 'failed';
            $result['error'] = $e->getMessage();
            $result['failed_at'] = now()->toIso8601String();
            
            // Attempt rollback
            if ($this->config['rollback']['auto_rollback']) {
                $this->log('Attempting automatic rollback', 'error');
                $rollbackResult = $this->rollback();
                $result['steps']['rollback'] = $rollbackResult;
            }
            
            // Send failure notification
            $this->sendNotification('Deployment failed', $result, 'error');
            
            throw $e;
        }
        
        // Store deployment record
        $this->storeDeploymentRecord($result);
        
        return $result;
    }
    
    /**
     * Run pre-deployment checks
     */
    protected function runPreDeploymentChecks(): array
    {
        $checks = [];
        $passed = true;
        $failures = [];
        
        // Check database connectivity
        if ($this->config['checks']['database']) {
            try {
                DB::connection()->getPdo();
                $checks['database'] = ['status' => 'passed', 'message' => 'Database connection successful'];
            } catch (\Exception $e) {
                $checks['database'] = ['status' => 'failed', 'message' => $e->getMessage()];
                $failures[] = 'Database connection failed';
                $passed = false;
            }
        }
        
        // Check pending migrations
        if ($this->config['checks']['migrations']) {
            try {
                $pending = $this->getPendingMigrations();
                if (empty($pending)) {
                    $checks['migrations'] = ['status' => 'passed', 'message' => 'No pending migrations'];
                } else {
                    $checks['migrations'] = [
                        'status' => 'info',
                        'message' => count($pending) . ' pending migrations found',
                        'migrations' => $pending
                    ];
                }
            } catch (\Exception $e) {
                $checks['migrations'] = ['status' => 'failed', 'message' => $e->getMessage()];
                $failures[] = 'Migration check failed';
                $passed = false;
            }
        }
        
        // Run tests
        if ($this->config['checks']['tests']) {
            try {
                $testResult = $this->runTests();
                if ($testResult['passed']) {
                    $checks['tests'] = ['status' => 'passed', 'message' => 'All tests passed'];
                } else {
                    $checks['tests'] = ['status' => 'failed', 'message' => 'Some tests failed', 'failures' => $testResult['failures']];
                    $failures[] = 'Tests failed';
                    $passed = false;
                }
            } catch (\Exception $e) {
                $checks['tests'] = ['status' => 'failed', 'message' => $e->getMessage()];
                $failures[] = 'Test execution failed';
                $passed = false;
            }
        }
        
        // Check disk space
        if ($this->config['checks']['disk_space']) {
            $diskSpace = $this->checkDiskSpace();
            if ($diskSpace['sufficient']) {
                $checks['disk_space'] = [
                    'status' => 'passed',
                    'message' => 'Sufficient disk space available',
                    'available' => $diskSpace['available']
                ];
            } else {
                $checks['disk_space'] = [
                    'status' => 'failed',
                    'message' => 'Insufficient disk space',
                    'available' => $diskSpace['available'],
                    'required' => $diskSpace['required']
                ];
                $failures[] = 'Insufficient disk space';
                $passed = false;
            }
        }
        
        // Check external services
        if ($this->config['checks']['services']) {
            $servicesResult = $this->checkExternalServices();
            $checks['services'] = $servicesResult;
            if (!$servicesResult['all_healthy']) {
                $failures[] = 'Some external services are unavailable';
                $passed = false;
            }
        }
        
        return [
            'passed' => $passed,
            'checks' => $checks,
            'failures' => $failures,
            'timestamp' => now()->toIso8601String()
        ];
    }
    
    /**
     * Create backup point for rollback
     */
    protected function createBackupPoint(): array
    {
        $backupId = 'deployment_' . $this->deploymentId;
        $backupPath = 'backups/deployments/' . $backupId;
        
        try {
            // Backup database
            $dbBackup = $this->backupDatabase($backupPath);
            
            // Backup current code
            $codeBackup = $this->backupCode($backupPath);
            
            // Backup configuration
            $configBackup = $this->backupConfiguration($backupPath);
            
            // Store backup metadata
            $metadata = [
                'backup_id' => $backupId,
                'created_at' => now()->toIso8601String(),
                'deployment_id' => $this->deploymentId,
                'database' => $dbBackup,
                'code' => $codeBackup,
                'config' => $configBackup
            ];
            
            Storage::put($backupPath . '/metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
            
            $this->rollbackData = $metadata;
            
            return [
                'status' => 'success',
                'backup_id' => $backupId,
                'location' => $backupPath,
                'components' => ['database', 'code', 'configuration']
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Deploy new code
     */
    protected function deployCode(array $options): array
    {
        try {
            // Pull latest code
            if ($options['git_pull'] ?? true) {
                exec('git pull origin ' . ($options['branch'] ?? 'main') . ' 2>&1', $output, $returnCode);
                
                if ($returnCode !== 0) {
                    throw new \Exception('Git pull failed: ' . implode("\n", $output));
                }
            }
            
            // Install composer dependencies
            if ($options['composer_install'] ?? true) {
                exec('composer install --no-interaction --prefer-dist --optimize-autoloader 2>&1', $output, $returnCode);
                
                if ($returnCode !== 0) {
                    throw new \Exception('Composer install failed: ' . implode("\n", $output));
                }
            }
            
            // Build frontend assets
            if ($options['npm_build'] ?? true) {
                exec('npm ci && npm run build 2>&1', $output, $returnCode);
                
                if ($returnCode !== 0) {
                    throw new \Exception('NPM build failed: ' . implode("\n", $output));
                }
            }
            
            return [
                'status' => 'success',
                'git_output' => $output ?? [],
                'timestamp' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Run database migrations safely
     */
    protected function runMigrations(): array
    {
        try {
            // First, check if migrations will succeed
            Artisan::call('migrate:status');
            
            // Run migrations with force flag
            Artisan::call('migrate', [
                '--force' => true,
                '--step' => true // Run one by one for easier rollback
            ]);
            
            $output = Artisan::output();
            
            return [
                'status' => 'success',
                'output' => $output,
                'timestamp' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Clear all caches
     */
    protected function clearCaches(): array
    {
        try {
            $commands = [
                'cache:clear' => 'Application cache cleared',
                'config:clear' => 'Configuration cache cleared',
                'route:clear' => 'Route cache cleared',
                'view:clear' => 'View cache cleared',
                'optimize:clear' => 'All caches cleared'
            ];
            
            $results = [];
            
            foreach ($commands as $command => $description) {
                Artisan::call($command);
                $results[$command] = $description;
            }
            
            // Rebuild caches
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            
            $results['rebuild'] = 'Caches rebuilt';
            
            return [
                'status' => 'success',
                'actions' => $results,
                'timestamp' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Run post-deployment tests
     */
    protected function runPostDeploymentTests(): array
    {
        try {
            $tests = [];
            $passed = true;
            
            // Run critical path tests
            $criticalTests = $this->runCriticalPathTests();
            $tests['critical_paths'] = $criticalTests;
            if (!$criticalTests['passed']) {
                $passed = false;
            }
            
            // Test database connectivity
            $dbTest = $this->testDatabaseConnectivity();
            $tests['database'] = $dbTest;
            if (!$dbTest['passed']) {
                $passed = false;
            }
            
            // Test external integrations
            $integrationTests = $this->testIntegrations();
            $tests['integrations'] = $integrationTests;
            if (!$integrationTests['all_passed']) {
                $passed = false;
            }
            
            // Test API endpoints
            $apiTests = $this->testApiEndpoints();
            $tests['api'] = $apiTests;
            if (!$apiTests['all_passed']) {
                $passed = false;
            }
            
            return [
                'passed' => $passed,
                'tests' => $tests,
                'timestamp' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            return [
                'passed' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Warm up application for zero-downtime deployment
     */
    protected function warmUpApplication(): array
    {
        try {
            $routes = [
                '/',
                '/admin',
                '/health',
                '/api/health'
            ];
            
            $results = [];
            
            foreach ($routes as $route) {
                $response = Http::timeout(10)->get(config('app.url') . $route);
                $results[$route] = [
                    'status' => $response->status(),
                    'time' => $response->transferStats->getTransferTime()
                ];
            }
            
            // Warm up cache
            Cache::remember('deployment_warmup', 60, fn() => 'warmed');
            
            // Sleep for configured warm-up time
            sleep($this->config['zero_downtime']['warm_up_time']);
            
            return [
                'status' => 'success',
                'routes_warmed' => count($routes),
                'results' => $results
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Switch traffic to new version (zero-downtime)
     */
    protected function switchTraffic(): array
    {
        try {
            $strategy = $this->config['zero_downtime']['strategy'];
            
            switch ($strategy) {
                case 'blue_green':
                    return $this->switchBlueGreen();
                    
                case 'rolling':
                    return $this->switchRolling();
                    
                default:
                    throw new \Exception('Unknown deployment strategy: ' . $strategy);
            }
            
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Run health check
     */
    protected function runHealthCheck(): array
    {
        try {
            $healthUrl = config('app.url') . $this->config['zero_downtime']['health_check_url'];
            $response = Http::timeout(30)->get($healthUrl);
            
            $healthy = $response->successful() && 
                      ($response->json()['status'] ?? '') === 'healthy';
            
            return [
                'healthy' => $healthy,
                'response' => $response->json(),
                'status_code' => $response->status(),
                'timestamp' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Monitor deployment for issues
     */
    protected function monitorDeployment(): array
    {
        $duration = 60; // Monitor for 1 minute
        $interval = 5; // Check every 5 seconds
        $checks = [];
        $issues = [];
        
        $startTime = time();
        
        while (time() - $startTime < $duration) {
            $check = [
                'timestamp' => now()->toIso8601String(),
                'health' => $this->quickHealthCheck(),
                'errors' => $this->checkErrorLogs(),
                'performance' => $this->checkPerformance()
            ];
            
            if (!$check['health']['healthy']) {
                $issues[] = 'Health check failed at ' . $check['timestamp'];
            }
            
            if ($check['errors']['count'] > 0) {
                $issues[] = 'New errors detected: ' . $check['errors']['count'];
            }
            
            if ($check['performance']['response_time'] > 3000) {
                $issues[] = 'Slow response time: ' . $check['performance']['response_time'] . 'ms';
            }
            
            $checks[] = $check;
            
            if (!empty($issues) && $this->config['rollback']['auto_rollback']) {
                break; // Stop monitoring and prepare for rollback
            }
            
            sleep($interval);
        }
        
        return [
            'duration' => time() - $startTime,
            'checks' => count($checks),
            'issues' => $issues,
            'status' => empty($issues) ? 'healthy' : 'issues_detected'
        ];
    }
    
    /**
     * Rollback deployment
     */
    public function rollback(): array
    {
        try {
            $this->log('Starting rollback procedure', 'warning');
            
            // Enable maintenance mode
            Artisan::call('down', ['--message' => 'Emergency rollback in progress']);
            
            // Restore database
            if (isset($this->rollbackData['database'])) {
                $this->restoreDatabase($this->rollbackData['database']);
            }
            
            // Restore code
            if (isset($this->rollbackData['code'])) {
                $this->restoreCode($this->rollbackData['code']);
            }
            
            // Restore configuration
            if (isset($this->rollbackData['config'])) {
                $this->restoreConfiguration($this->rollbackData['config']);
            }
            
            // Clear caches
            $this->clearCaches();
            
            // Run health check
            $health = $this->runHealthCheck();
            
            // Disable maintenance mode if healthy
            if ($health['healthy']) {
                Artisan::call('up');
            }
            
            return [
                'status' => 'success',
                'health' => $health,
                'timestamp' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send deployment notification
     */
    protected function sendNotification(string $subject, array $data, string $level = 'info'): void
    {
        try {
            // Slack notification
            if ($webhook = $this->config['notifications']['slack']) {
                Http::post($webhook, [
                    'text' => $subject,
                    'attachments' => [[
                        'color' => $level === 'error' ? 'danger' : 'good',
                        'fields' => [
                            ['title' => 'Deployment ID', 'value' => $data['deployment_id']],
                            ['title' => 'Status', 'value' => $data['status']],
                            ['title' => 'Duration', 'value' => ($data['duration'] ?? 'N/A') . 's']
                        ]
                    ]]
                ]);
            }
            
            // Email notification
            if ($email = $this->config['notifications']['email']) {
                // Implement email notification
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to send deployment notification', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Store deployment record
     */
    protected function storeDeploymentRecord(array $result): void
    {
        $path = 'deployments/' . $this->deploymentId . '.json';
        Storage::put($path, json_encode($result, JSON_PRETTY_PRINT));
    }
    
    /**
     * Clean up old backups
     */
    protected function cleanupOldBackups(): void
    {
        $retention = $this->config['rollback']['backup_retention'];
        $cutoff = now()->subDays($retention);
        
        $backups = Storage::directories('backups/deployments');
        
        foreach ($backups as $backup) {
            $metadataPath = $backup . '/metadata.json';
            
            if (Storage::exists($metadataPath)) {
                $metadata = json_decode(Storage::get($metadataPath), true);
                $createdAt = Carbon::parse($metadata['created_at']);
                
                if ($createdAt->isBefore($cutoff)) {
                    Storage::deleteDirectory($backup);
                    $this->log('Deleted old backup: ' . $backup);
                }
            }
        }
    }
    
    /**
     * Log deployment message
     */
    protected function log(string $message, string $level = 'info'): void
    {
        Log::channel('deployment')->{$level}($message, [
            'deployment_id' => $this->deploymentId
        ]);
    }
    
    /**
     * Get pending migrations
     */
    protected function getPendingMigrations(): array
    {
        Artisan::call('migrate:status');
        $output = Artisan::output();
        
        $pending = [];
        $lines = explode("\n", $output);
        
        foreach ($lines as $line) {
            if (strpos($line, 'Pending') !== false) {
                preg_match('/\s+(\S+)\s+/', $line, $matches);
                if (isset($matches[1])) {
                    $pending[] = $matches[1];
                }
            }
        }
        
        return $pending;
    }
    
    /**
     * Run test suite
     */
    protected function runTests(): array
    {
        exec('php artisan test --parallel 2>&1', $output, $returnCode);
        
        return [
            'passed' => $returnCode === 0,
            'output' => $output,
            'failures' => $returnCode === 0 ? [] : $this->parseTestFailures($output)
        ];
    }
    
    /**
     * Parse test failures from output
     */
    protected function parseTestFailures(array $output): array
    {
        $failures = [];
        $inFailure = false;
        $currentFailure = [];
        
        foreach ($output as $line) {
            if (strpos($line, 'FAILED') !== false) {
                $inFailure = true;
                $currentFailure = [$line];
            } elseif ($inFailure && strpos($line, '---') !== false) {
                $failures[] = implode("\n", $currentFailure);
                $inFailure = false;
                $currentFailure = [];
            } elseif ($inFailure) {
                $currentFailure[] = $line;
            }
        }
        
        return $failures;
    }
    
    /**
     * Check disk space
     */
    protected function checkDiskSpace(): array
    {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        $used = $total - $free;
        $percentage = round(($used / $total) * 100, 2);
        
        $requiredGb = 2; // Require at least 2GB free
        $requiredBytes = $requiredGb * 1024 * 1024 * 1024;
        
        return [
            'sufficient' => $free > $requiredBytes,
            'available' => $this->formatBytes($free),
            'total' => $this->formatBytes($total),
            'used_percentage' => $percentage,
            'required' => $this->formatBytes($requiredBytes)
        ];
    }
    
    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Check external services
     */
    protected function checkExternalServices(): array
    {
        $services = [
            'calcom' => config('services.calcom.base_url') . '/api/health',
            'retell' => 'https://api.retellai.com/health',
            'redis' => null, // Check separately
            'mysql' => null  // Check separately
        ];
        
        $results = [];
        $allHealthy = true;
        
        // Check HTTP services
        foreach ($services as $name => $url) {
            if ($url) {
                try {
                    $response = Http::timeout(5)->get($url);
                    $results[$name] = [
                        'status' => $response->successful() ? 'healthy' : 'unhealthy',
                        'response_time' => $response->transferStats->getTransferTime()
                    ];
                    
                    if (!$response->successful()) {
                        $allHealthy = false;
                    }
                } catch (\Exception $e) {
                    $results[$name] = [
                        'status' => 'error',
                        'error' => $e->getMessage()
                    ];
                    $allHealthy = false;
                }
            }
        }
        
        // Check Redis
        try {
            Cache::store('redis')->put('deployment_check', true, 10);
            $results['redis'] = ['status' => 'healthy'];
        } catch (\Exception $e) {
            $results['redis'] = ['status' => 'error', 'error' => $e->getMessage()];
            $allHealthy = false;
        }
        
        // Check MySQL
        try {
            DB::connection()->getPdo();
            $results['mysql'] = ['status' => 'healthy'];
        } catch (\Exception $e) {
            $results['mysql'] = ['status' => 'error', 'error' => $e->getMessage()];
            $allHealthy = false;
        }
        
        return [
            'all_healthy' => $allHealthy,
            'services' => $results
        ];
    }
    
    /**
     * Backup database
     */
    protected function backupDatabase(string $backupPath): array
    {
        $filename = 'database_' . date('Y_m_d_His') . '.sql';
        $filepath = storage_path('app/' . $backupPath . '/' . $filename);
        
        // Create directory
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Execute mysqldump
        $command = sprintf(
            'mysqldump -h%s -P%s -u%s -p%s %s > %s 2>&1',
            config('database.connections.mysql.host'),
            config('database.connections.mysql.port'),
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
            $filepath
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception('Database backup failed: ' . implode("\n", $output));
        }
        
        // Compress backup
        exec('gzip ' . $filepath);
        
        return [
            'filename' => $filename . '.gz',
            'size' => filesize($filepath . '.gz'),
            'path' => $backupPath . '/' . $filename . '.gz'
        ];
    }
    
    /**
     * Backup code
     */
    protected function backupCode(string $backupPath): array
    {
        $filename = 'code_' . date('Y_m_d_His') . '.tar.gz';
        $filepath = storage_path('app/' . $backupPath . '/' . $filename);
        
        // Create directory
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Create tar archive (excluding vendor and node_modules)
        $command = sprintf(
            'tar -czf %s --exclude=vendor --exclude=node_modules --exclude=storage --exclude=.git -C %s .',
            $filepath,
            base_path()
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception('Code backup failed');
        }
        
        return [
            'filename' => $filename,
            'size' => filesize($filepath),
            'path' => $backupPath . '/' . $filename
        ];
    }
    
    /**
     * Backup configuration
     */
    protected function backupConfiguration(string $backupPath): array
    {
        $configs = [
            '.env' => base_path('.env'),
            'config' => config_path()
        ];
        
        $filename = 'config_' . date('Y_m_d_His') . '.tar.gz';
        $filepath = storage_path('app/' . $backupPath . '/' . $filename);
        
        // Create directory
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Create temporary directory for configs
        $tempDir = storage_path('app/temp_config_backup');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        // Copy configs
        foreach ($configs as $name => $path) {
            if (is_file($path)) {
                copy($path, $tempDir . '/' . $name);
            } elseif (is_dir($path)) {
                exec('cp -r ' . $path . ' ' . $tempDir . '/' . $name);
            }
        }
        
        // Create tar archive
        exec('tar -czf ' . $filepath . ' -C ' . $tempDir . ' .');
        
        // Clean up temp directory
        exec('rm -rf ' . $tempDir);
        
        return [
            'filename' => $filename,
            'size' => filesize($filepath),
            'path' => $backupPath . '/' . $filename
        ];
    }
    
    /**
     * Test critical paths
     */
    protected function runCriticalPathTests(): array
    {
        $paths = [
            'login' => '/admin/login',
            'dashboard' => '/admin',
            'appointments' => '/api/appointments',
            'webhooks' => '/api/retell/webhook'
        ];
        
        $results = [];
        $passed = true;
        
        foreach ($paths as $name => $path) {
            try {
                $response = Http::timeout(10)->get(config('app.url') . $path);
                $results[$name] = [
                    'status' => $response->status(),
                    'passed' => in_array($response->status(), [200, 302, 401]) // Auth redirects are OK
                ];
                
                if (!$results[$name]['passed']) {
                    $passed = false;
                }
            } catch (\Exception $e) {
                $results[$name] = [
                    'passed' => false,
                    'error' => $e->getMessage()
                ];
                $passed = false;
            }
        }
        
        return [
            'passed' => $passed,
            'paths' => $results
        ];
    }
    
    /**
     * Test database connectivity
     */
    protected function testDatabaseConnectivity(): array
    {
        try {
            // Test read
            $count = DB::table('companies')->count();
            
            // Test write (in transaction)
            DB::beginTransaction();
            DB::table('deployment_tests')->insert([
                'test' => 'deployment_' . $this->deploymentId,
                'created_at' => now()
            ]);
            DB::rollBack();
            
            return [
                'passed' => true,
                'read_test' => 'success',
                'write_test' => 'success',
                'record_count' => $count
            ];
            
        } catch (\Exception $e) {
            return [
                'passed' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test integrations
     */
    protected function testIntegrations(): array
    {
        $integrations = [
            'calcom' => function () {
                $service = app(\App\Services\CalcomV2Service::class);
                return $service->testConnection();
            },
            'retell' => function () {
                $service = app(\App\Services\Retell\RetellService::class);
                return $service->testConnection();
            }
        ];
        
        $results = [];
        $allPassed = true;
        
        foreach ($integrations as $name => $test) {
            try {
                $result = $test();
                $results[$name] = [
                    'passed' => $result,
                    'status' => $result ? 'connected' : 'failed'
                ];
                
                if (!$result) {
                    $allPassed = false;
                }
            } catch (\Exception $e) {
                $results[$name] = [
                    'passed' => false,
                    'error' => $e->getMessage()
                ];
                $allPassed = false;
            }
        }
        
        return [
            'all_passed' => $allPassed,
            'integrations' => $results
        ];
    }
    
    /**
     * Test API endpoints
     */
    protected function testApiEndpoints(): array
    {
        $endpoints = [
            ['method' => 'GET', 'path' => '/api/health'],
            ['method' => 'GET', 'path' => '/api/appointments'],
            ['method' => 'GET', 'path' => '/api/customers']
        ];
        
        $results = [];
        $allPassed = true;
        
        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->{strtolower($endpoint['method'])}(config('app.url') . $endpoint['path']);
                
                $passed = $response->successful() || $response->status() === 401; // Auth required is OK
                
                $results[$endpoint['path']] = [
                    'method' => $endpoint['method'],
                    'status' => $response->status(),
                    'passed' => $passed
                ];
                
                if (!$passed) {
                    $allPassed = false;
                }
            } catch (\Exception $e) {
                $results[$endpoint['path']] = [
                    'method' => $endpoint['method'],
                    'passed' => false,
                    'error' => $e->getMessage()
                ];
                $allPassed = false;
            }
        }
        
        return [
            'all_passed' => $allPassed,
            'endpoints' => $results
        ];
    }
    
    /**
     * Blue-green deployment switch
     */
    protected function switchBlueGreen(): array
    {
        // This would integrate with load balancer or reverse proxy
        // For now, we'll simulate the switch
        
        return [
            'status' => 'success',
            'strategy' => 'blue_green',
            'previous_version' => 'blue',
            'current_version' => 'green',
            'switched_at' => now()->toIso8601String()
        ];
    }
    
    /**
     * Rolling deployment switch
     */
    protected function switchRolling(): array
    {
        // This would integrate with container orchestration
        // For now, we'll simulate the rolling update
        
        return [
            'status' => 'success',
            'strategy' => 'rolling',
            'instances_updated' => 4,
            'update_duration' => 120,
            'completed_at' => now()->toIso8601String()
        ];
    }
    
    /**
     * Quick health check
     */
    protected function quickHealthCheck(): array
    {
        try {
            $response = Http::timeout(5)->get(config('app.url') . '/health');
            
            return [
                'healthy' => $response->successful(),
                'status_code' => $response->status()
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check error logs
     */
    protected function checkErrorLogs(): array
    {
        $logFile = storage_path('logs/laravel.log');
        $lastCheck = Cache::get('deployment_last_error_check', 0);
        $currentSize = filesize($logFile);
        
        $newErrors = 0;
        
        if ($currentSize > $lastCheck) {
            // Count new error entries
            $handle = fopen($logFile, 'r');
            fseek($handle, $lastCheck);
            
            while (!feof($handle)) {
                $line = fgets($handle);
                if (strpos($line, '.ERROR:') !== false || strpos($line, '.CRITICAL:') !== false) {
                    $newErrors++;
                }
            }
            
            fclose($handle);
        }
        
        Cache::put('deployment_last_error_check', $currentSize, 3600);
        
        return [
            'count' => $newErrors,
            'log_size' => $currentSize
        ];
    }
    
    /**
     * Check performance metrics
     */
    protected function checkPerformance(): array
    {
        $urls = [
            '/' => 'home',
            '/admin' => 'admin',
            '/api/health' => 'api'
        ];
        
        $totalTime = 0;
        $count = 0;
        
        foreach ($urls as $url => $name) {
            try {
                $start = microtime(true);
                $response = Http::timeout(5)->get(config('app.url') . $url);
                $time = (microtime(true) - $start) * 1000; // Convert to ms
                
                $totalTime += $time;
                $count++;
            } catch (\Exception $e) {
                // Skip failed requests
            }
        }
        
        return [
            'response_time' => $count > 0 ? round($totalTime / $count) : 0,
            'checked_endpoints' => $count
        ];
    }
    
    /**
     * Restore database from backup
     */
    protected function restoreDatabase(array $backupInfo): void
    {
        $filepath = storage_path('app/' . $backupInfo['path']);
        
        // Decompress if needed
        if (substr($filepath, -3) === '.gz') {
            exec('gunzip -c ' . $filepath . ' > ' . substr($filepath, 0, -3));
            $filepath = substr($filepath, 0, -3);
        }
        
        // Restore database
        $command = sprintf(
            'mysql -h%s -P%s -u%s -p%s %s < %s 2>&1',
            config('database.connections.mysql.host'),
            config('database.connections.mysql.port'),
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
            $filepath
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception('Database restore failed: ' . implode("\n", $output));
        }
    }
    
    /**
     * Restore code from backup
     */
    protected function restoreCode(array $backupInfo): void
    {
        $filepath = storage_path('app/' . $backupInfo['path']);
        
        // Extract to temporary directory first
        $tempDir = storage_path('app/temp_code_restore');
        exec('mkdir -p ' . $tempDir);
        exec('tar -xzf ' . $filepath . ' -C ' . $tempDir);
        
        // Sync files (excluding critical directories)
        exec('rsync -av --exclude=storage --exclude=vendor --exclude=node_modules ' . $tempDir . '/ ' . base_path() . '/');
        
        // Clean up
        exec('rm -rf ' . $tempDir);
        
        // Reinstall dependencies
        exec('composer install --no-interaction --prefer-dist --optimize-autoloader');
        exec('npm ci');
    }
    
    /**
     * Restore configuration
     */
    protected function restoreConfiguration(array $backupInfo): void
    {
        $filepath = storage_path('app/' . $backupInfo['path']);
        
        // Extract to temporary directory
        $tempDir = storage_path('app/temp_config_restore');
        exec('mkdir -p ' . $tempDir);
        exec('tar -xzf ' . $filepath . ' -C ' . $tempDir);
        
        // Restore .env
        if (file_exists($tempDir . '/.env')) {
            copy($tempDir . '/.env', base_path('.env'));
        }
        
        // Restore config files
        if (is_dir($tempDir . '/config')) {
            exec('cp -r ' . $tempDir . '/config/* ' . config_path() . '/');
        }
        
        // Clean up
        exec('rm -rf ' . $tempDir);
    }
}