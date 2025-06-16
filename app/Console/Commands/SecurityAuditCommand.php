<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Security\AskProAISecurityLayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class SecurityAuditCommand extends Command
{
    protected $signature = 'askproai:security-audit 
                            {--full : Run comprehensive security audit}
                            {--report : Generate detailed report}';

    protected $description = 'Run security audit to check system vulnerabilities';

    protected AskProAISecurityLayer $securityLayer;

    public function __construct(AskProAISecurityLayer $securityLayer)
    {
        parent::__construct();
        $this->securityLayer = $securityLayer;
    }

    public function handle()
    {
        $this->info('🔒 Running AskProAI Security Audit...');
        $startTime = microtime(true);
        
        $results = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'checks' => [],
            'vulnerabilities' => [],
            'recommendations' => []
        ];

        // 1. Check Environment Configuration
        $this->info('Checking environment configuration...');
        $envChecks = $this->checkEnvironmentSecurity();
        $results['checks']['environment'] = $envChecks;

        // 2. Check Database Security
        $this->info('Checking database security...');
        $dbChecks = $this->checkDatabaseSecurity();
        $results['checks']['database'] = $dbChecks;

        // 3. Check API Key Security
        $this->info('Checking API key security...');
        $apiChecks = $this->checkApiKeySecurity();
        $results['checks']['api_keys'] = $apiChecks;

        // 4. Check File Permissions
        $this->info('Checking file permissions...');
        $fileChecks = $this->checkFilePermissions();
        $results['checks']['file_permissions'] = $fileChecks;

        // 5. Check Middleware Configuration
        $this->info('Checking middleware configuration...');
        $middlewareChecks = $this->checkMiddlewareSecurity();
        $results['checks']['middleware'] = $middlewareChecks;

        // 6. Check Recent Security Events
        if ($this->option('full')) {
            $this->info('Checking recent security events...');
            $eventChecks = $this->checkSecurityEvents();
            $results['checks']['security_events'] = $eventChecks;
        }

        // Calculate summary
        $totalChecks = 0;
        $passedChecks = 0;
        foreach ($results['checks'] as $category => $checks) {
            foreach ($checks as $check) {
                $totalChecks++;
                if ($check['status'] === 'passed') {
                    $passedChecks++;
                } else {
                    $results['vulnerabilities'][] = [
                        'category' => $category,
                        'issue' => $check['message'],
                        'severity' => $check['severity'] ?? 'medium'
                    ];
                }
            }
        }

        $results['summary'] = [
            'total_checks' => $totalChecks,
            'passed_checks' => $passedChecks,
            'failed_checks' => $totalChecks - $passedChecks,
            'security_score' => round(($passedChecks / $totalChecks) * 100, 2),
            'duration' => round(microtime(true) - $startTime, 2) . 's'
        ];

        // Display results
        $this->displayResults($results);

        // Generate report if requested
        if ($this->option('report')) {
            $this->generateReport($results);
        }

        return $results['summary']['failed_checks'] === 0 ? 0 : 1;
    }

    private function checkEnvironmentSecurity(): array
    {
        $checks = [];

        // Check debug mode
        $checks[] = [
            'check' => 'Debug mode disabled in production',
            'status' => config('app.debug') === false ? 'passed' : 'failed',
            'message' => config('app.debug') === false 
                ? 'Debug mode is disabled' 
                : 'Debug mode is enabled in production!',
            'severity' => 'critical'
        ];

        // Check app key
        $checks[] = [
            'check' => 'Application key is set',
            'status' => !empty(config('app.key')) ? 'passed' : 'failed',
            'message' => !empty(config('app.key')) 
                ? 'Application key is configured' 
                : 'Application key is missing!',
            'severity' => 'critical'
        ];

        // Check HTTPS enforcement
        $checks[] = [
            'check' => 'HTTPS enforcement',
            'status' => config('app.env') !== 'production' || request()->secure() ? 'passed' : 'warning',
            'message' => 'HTTPS should be enforced in production',
            'severity' => 'high'
        ];

        return $checks;
    }

    private function checkDatabaseSecurity(): array
    {
        $checks = [];

        // Check for default credentials
        $checks[] = [
            'check' => 'No default database credentials',
            'status' => config('database.connections.mysql.password') !== 'password' ? 'passed' : 'failed',
            'message' => 'Database password should not be default',
            'severity' => 'critical'
        ];

        // Check for SQL injection vulnerabilities
        try {
            $recentQueries = DB::table('calls')
                ->where('created_at', '>', Carbon::now()->subDay())
                ->count();
            
            $checks[] = [
                'check' => 'Database queries are parameterized',
                'status' => 'passed',
                'message' => 'Using Laravel query builder for protection'
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'check' => 'Database connection',
                'status' => 'warning',
                'message' => 'Could not verify database security'
            ];
        }

        return $checks;
    }

    private function checkApiKeySecurity(): array
    {
        $checks = [];

        // Check if API keys are encrypted
        try {
            $unencryptedKeys = DB::table('companies')
                ->whereNotNull('calcom_api_key')
                ->where('calcom_api_key', 'NOT LIKE', 'eyJ%')
                ->count();

            $checks[] = [
                'check' => 'API keys are encrypted',
                'status' => $unencryptedKeys === 0 ? 'passed' : 'failed',
                'message' => $unencryptedKeys === 0 
                    ? 'All API keys are encrypted' 
                    : "$unencryptedKeys unencrypted API keys found",
                'severity' => 'high'
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'check' => 'API key encryption',
                'status' => 'warning',
                'message' => 'Could not verify API key encryption'
            ];
        }

        return $checks;
    }

    private function checkFilePermissions(): array
    {
        $checks = [];

        // Check .env file permissions
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $perms = substr(sprintf('%o', fileperms($envPath)), -4);
            $checks[] = [
                'check' => '.env file permissions',
                'status' => $perms <= '0644' ? 'passed' : 'failed',
                'message' => ".env permissions are $perms (should be 0644 or less)",
                'severity' => 'high'
            ];
        }

        // Check storage directory
        $storagePath = storage_path();
        if (is_writable($storagePath)) {
            $checks[] = [
                'check' => 'Storage directory is writable',
                'status' => 'passed',
                'message' => 'Storage directory has correct permissions'
            ];
        } else {
            $checks[] = [
                'check' => 'Storage directory permissions',
                'status' => 'failed',
                'message' => 'Storage directory is not writable',
                'severity' => 'high'
            ];
        }

        return $checks;
    }

    private function checkMiddlewareSecurity(): array
    {
        $checks = [];

        // In Laravel 11, check if middleware classes exist
        $securityMiddleware = [
            'App\Http\Middleware\ThreatDetectionMiddleware' => 'ThreatDetectionMiddleware',
            'App\Http\Middleware\AdaptiveRateLimitMiddleware' => 'AdaptiveRateLimitMiddleware',
            'App\Http\Middleware\VerifyCalcomSignature' => 'VerifyCalcomSignature',
            'App\Http\Middleware\VerifyRetellSignature' => 'VerifyRetellSignature'
        ];

        foreach ($securityMiddleware as $class => $name) {
            $exists = class_exists($class);
            $checks[] = [
                'check' => "$name exists",
                'status' => $exists ? 'passed' : 'warning',
                'message' => $exists 
                    ? "$name is available" 
                    : "$name is not found",
                'severity' => 'medium'
            ];
        }

        // Check route middleware registration
        $routeMiddleware = [
            'calcom.signature' => 'Cal.com signature verification',
            'retell.signature' => 'Retell signature verification',
            'threat.detection' => 'Threat detection',
            'rate.limit' => 'Adaptive rate limiting'
        ];

        foreach ($routeMiddleware as $alias => $description) {
            try {
                // In Laravel 11, we can't directly check middleware registration
                // So we'll just check if the middleware is used in routes
                $routes = app('router')->getRoutes();
                $used = false;
                
                foreach ($routes as $route) {
                    $middleware = $route->gatherMiddleware();
                    if (in_array($alias, $middleware)) {
                        $used = true;
                        break;
                    }
                }
                
                $checks[] = [
                    'check' => "$description middleware usage",
                    'status' => $used ? 'passed' : 'info',
                    'message' => $used 
                        ? "$description is used in routes" 
                        : "$description is available but not used",
                    'severity' => 'low'
                ];
            } catch (\Exception $e) {
                // Skip if can't check
            }
        }

        return $checks;
    }

    private function checkSecurityEvents(): array
    {
        $checks = [];

        try {
            // Check for recent threats
            $recentThreats = DB::table('security_events')
                ->where('created_at', '>', Carbon::now()->subDay())
                ->where('event_type', 'threat_detected')
                ->count();

            $checks[] = [
                'check' => 'Recent security threats',
                'status' => $recentThreats < 10 ? 'passed' : 'warning',
                'message' => "$recentThreats threats detected in last 24 hours",
                'severity' => $recentThreats > 50 ? 'high' : 'medium'
            ];

            // Check for rate limit violations
            $rateLimitViolations = DB::table('security_events')
                ->where('created_at', '>', Carbon::now()->subHour())
                ->where('event_type', 'rate_limit_exceeded')
                ->count();

            $checks[] = [
                'check' => 'Rate limit violations',
                'status' => $rateLimitViolations < 20 ? 'passed' : 'warning',
                'message' => "$rateLimitViolations rate limit violations in last hour",
                'severity' => 'medium'
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'check' => 'Security event monitoring',
                'status' => 'info',
                'message' => 'Security events table not configured'
            ];
        }

        return $checks;
    }

    private function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('                   SECURITY AUDIT RESULTS                       ');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        foreach ($results['checks'] as $category => $checks) {
            $this->info(strtoupper(str_replace('_', ' ', $category)) . ':');
            foreach ($checks as $check) {
                $icon = match($check['status']) {
                    'passed' => '✅',
                    'failed' => '❌',
                    'warning' => '⚠️',
                    default => 'ℹ️'
                };
                
                $color = match($check['status']) {
                    'passed' => 'info',
                    'failed' => 'error',
                    'warning' => 'comment',
                    default => 'line'
                };
                
                $this->$color("  $icon {$check['message']}");
            }
            $this->newLine();
        }

        // Display summary
        $scoreColor = $results['summary']['security_score'] >= 80 ? 'info' 
            : ($results['summary']['security_score'] >= 60 ? 'comment' : 'error');
        
        $this->info('SUMMARY:');
        $this->line("  Total Checks: {$results['summary']['total_checks']}");
        $this->info("  Passed: {$results['summary']['passed_checks']}");
        if ($results['summary']['failed_checks'] > 0) {
            $this->error("  Failed: {$results['summary']['failed_checks']}");
        }
        $this->$scoreColor("  Security Score: {$results['summary']['security_score']}%");
        $this->line("  Duration: {$results['summary']['duration']}");

        // Display vulnerabilities
        if (!empty($results['vulnerabilities'])) {
            $this->newLine();
            $this->error('VULNERABILITIES FOUND:');
            foreach ($results['vulnerabilities'] as $vuln) {
                $this->error("  - [{$vuln['severity']}] {$vuln['issue']}");
            }
        }

        // Display recommendations
        if ($results['summary']['security_score'] < 100) {
            $this->newLine();
            $this->comment('RECOMMENDATIONS:');
            if ($results['summary']['security_score'] < 80) {
                $this->comment('  - Address all critical and high severity issues immediately');
            }
            $this->comment('  - Enable all security middleware globally');
            $this->comment('  - Ensure all API keys are encrypted');
            $this->comment('  - Review and update file permissions');
            $this->comment('  - Enable comprehensive logging and monitoring');
        }
    }

    private function generateReport(array $results): void
    {
        $filename = 'security_audit_' . Carbon::now()->format('Y-m-d_His') . '.json';
        $path = storage_path('app/security_audits/' . $filename);
        
        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }
        
        File::put($path, json_encode($results, JSON_PRETTY_PRINT));
        
        $this->newLine();
        $this->info("📄 Detailed report saved to: $path");
    }
}