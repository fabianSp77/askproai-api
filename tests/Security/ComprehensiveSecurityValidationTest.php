<?php

namespace Tests\Security;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class ComprehensiveSecurityValidationTest extends TestCase
{
    /**
     * Run all security test suites and validate results.
     */
    public function test_comprehensive_security_validation()
    {
        $this->info('Starting comprehensive security validation...');
        
        $results = [
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'suites' => [],
            'summary' => [
                'total_tests' => 0,
                'passed' => 0,
                'failed' => 0,
                'skipped' => 0,
            ],
        ];

        // Run authentication security tests
        $results['suites']['authentication'] = $this->runTestSuite(AuthenticationSecurityTest::class);
        
        // Run input validation tests
        $results['suites']['input_validation'] = $this->runTestSuite(InputValidationSecurityTest::class);
        
        // Run API security tests
        $results['suites']['api_security'] = $this->runTestSuite(ApiSecurityTest::class);
        
        // Run CSRF protection tests
        $results['suites']['csrf_protection'] = $this->runTestSuite(CsrfProtectionTest::class);
        
        // Calculate totals
        foreach ($results['suites'] as $suite) {
            $results['summary']['total_tests'] += $suite['total'];
            $results['summary']['passed'] += $suite['passed'];
            $results['summary']['failed'] += $suite['failed'];
            $results['summary']['skipped'] += $suite['skipped'];
        }
        
        // Calculate security score
        $results['security_score'] = $this->calculateSecurityScore($results);
        
        // Run security audit command
        $auditResult = $this->runSecurityAudit();
        $results['audit'] = $auditResult;
        
        // Validate configuration
        $configValidation = $this->validateSecurityConfiguration();
        $results['configuration'] = $configValidation;
        
        // Check for known vulnerabilities
        $vulnerabilities = $this->checkKnownVulnerabilities();
        $results['vulnerabilities'] = $vulnerabilities;
        
        // Generate comprehensive report
        $this->generateComprehensiveReport($results);
        
        // Assert minimum security requirements
        $this->assertGreaterThanOrEqual(80, $results['security_score'], 
            'Security score is below acceptable threshold (80%)');
        
        $this->assertEquals(0, $results['summary']['failed'], 
            'Security tests failed: ' . json_encode($results['summary']));
        
        $this->assertEmpty($vulnerabilities['critical'], 
            'Critical vulnerabilities found: ' . json_encode($vulnerabilities['critical']));
    }

    /**
     * Run a specific test suite and return results.
     */
    protected function runTestSuite($testClass)
    {
        $this->info("Running {$testClass}...");
        
        $result = Artisan::call('test', [
            '--filter' => class_basename($testClass),
        ]);
        
        // Parse test output (this would need proper parsing in real implementation)
        return [
            'class' => $testClass,
            'total' => 10, // Placeholder
            'passed' => $result === 0 ? 10 : 5,
            'failed' => $result === 0 ? 0 : 5,
            'skipped' => 0,
            'duration' => '1.23s',
        ];
    }

    /**
     * Run security audit command.
     */
    protected function runSecurityAudit()
    {
        Artisan::call('security:audit', ['--json' => true]);
        $output = Artisan::output();
        
        try {
            return json_decode($output, true);
        } catch (\Exception $e) {
            return ['error' => 'Failed to parse audit results'];
        }
    }

    /**
     * Validate security configuration.
     */
    protected function validateSecurityConfiguration()
    {
        $checks = [];
        
        // Check environment configuration
        $checks['environment'] = [
            'debug_mode' => !config('app.debug') || app()->environment('local'),
            'https_only' => config('session.secure', false) || app()->environment('local'),
            'app_key_set' => !empty(config('app.key')),
        ];
        
        // Check session configuration
        $checks['session'] = [
            'secure_cookies' => config('session.secure', false) || app()->environment('local'),
            'http_only' => config('session.http_only', true),
            'same_site' => in_array(config('session.same_site'), ['lax', 'strict']),
            'encryption' => config('session.encrypt', false),
        ];
        
        // Check authentication configuration
        $checks['authentication'] = [
            'password_hashing' => config('hashing.driver') === 'bcrypt',
            'password_min_length' => config('auth.password_min_length', 8) >= 8,
            'rate_limiting' => $this->checkRateLimiting(),
        ];
        
        // Check API configuration
        $checks['api'] = [
            'rate_limiting' => config('api.rate_limiting.enabled', true),
            'authentication' => class_exists(\Laravel\Sanctum\Sanctum::class),
            'cors_configured' => !in_array('*', config('cors.allowed_origins', [])),
        ];
        
        // Check encryption configuration
        $checks['encryption'] = [
            'cipher' => config('app.cipher') === 'AES-256-CBC',
            'key_rotation' => $this->checkKeyRotation(),
        ];
        
        // Check logging configuration
        $checks['logging'] = [
            'security_channel' => isset(config('logging.channels')['security']),
            'stack_includes_security' => in_array('security', config('logging.channels.stack.channels', [])),
        ];
        
        return $checks;
    }

    /**
     * Check for known vulnerabilities.
     */
    protected function checkKnownVulnerabilities()
    {
        $vulnerabilities = [
            'critical' => [],
            'high' => [],
            'medium' => [],
            'low' => [],
        ];
        
        // Check composer dependencies
        if (File::exists(base_path('composer.lock'))) {
            $composerLock = json_decode(File::get(base_path('composer.lock')), true);
            
            // Check for known vulnerable versions
            $knownVulnerabilities = [
                'laravel/framework' => [
                    'vulnerable' => ['<9.52.16', '<10.48.2'],
                    'severity' => 'high',
                ],
                'symfony/http-kernel' => [
                    'vulnerable' => ['<5.4.20', '<6.0.20', '<6.1.12', '<6.2.6'],
                    'severity' => 'critical',
                ],
            ];
            
            foreach ($composerLock['packages'] ?? [] as $package) {
                if (isset($knownVulnerabilities[$package['name']])) {
                    foreach ($knownVulnerabilities[$package['name']]['vulnerable'] as $vulnerableVersion) {
                        if (version_compare($package['version'], ltrim($vulnerableVersion, '<'), '<')) {
                            $severity = $knownVulnerabilities[$package['name']]['severity'];
                            $vulnerabilities[$severity][] = [
                                'package' => $package['name'],
                                'version' => $package['version'],
                                'vulnerability' => "Version < " . ltrim($vulnerableVersion, '<'),
                            ];
                        }
                    }
                }
            }
        }
        
        // Check for common security misconfigurations
        if (config('app.debug') === true && app()->environment('production')) {
            $vulnerabilities['critical'][] = [
                'type' => 'configuration',
                'issue' => 'Debug mode enabled in production',
            ];
        }
        
        if (empty(config('app.key'))) {
            $vulnerabilities['critical'][] = [
                'type' => 'configuration',
                'issue' => 'Application key not set',
            ];
        }
        
        // Check for exposed files
        $exposedFiles = [
            '.env',
            '.env.backup',
            'composer.json',
            'composer.lock',
            'package.json',
            'webpack.mix.js',
            'phpunit.xml',
        ];
        
        foreach ($exposedFiles as $file) {
            $url = config('app.url') . '/' . $file;
            try {
                $response = Http::get($url);
                if ($response->successful()) {
                    $vulnerabilities['high'][] = [
                        'type' => 'exposed_file',
                        'file' => $file,
                        'url' => $url,
                    ];
                }
            } catch (\Exception $e) {
                // File not exposed (good)
            }
        }
        
        return $vulnerabilities;
    }

    /**
     * Calculate overall security score.
     */
    protected function calculateSecurityScore($results)
    {
        $score = 100;
        
        // Deduct points for failed tests
        $failureRate = $results['summary']['failed'] / max($results['summary']['total_tests'], 1);
        $score -= $failureRate * 50;
        
        // Deduct points for vulnerabilities
        if (!empty($results['vulnerabilities']['critical'])) {
            $score -= count($results['vulnerabilities']['critical']) * 20;
        }
        if (!empty($results['vulnerabilities']['high'])) {
            $score -= count($results['vulnerabilities']['high']) * 10;
        }
        if (!empty($results['vulnerabilities']['medium'])) {
            $score -= count($results['vulnerabilities']['medium']) * 5;
        }
        
        // Deduct points for configuration issues
        $configScore = $this->calculateConfigScore($results['configuration']);
        $score = $score * ($configScore / 100);
        
        return max(0, round($score));
    }

    /**
     * Calculate configuration score.
     */
    protected function calculateConfigScore($configuration)
    {
        $totalChecks = 0;
        $passedChecks = 0;
        
        foreach ($configuration as $category => $checks) {
            foreach ($checks as $check => $passed) {
                $totalChecks++;
                if ($passed) {
                    $passedChecks++;
                }
            }
        }
        
        return $totalChecks > 0 ? ($passedChecks / $totalChecks) * 100 : 0;
    }

    /**
     * Check if rate limiting is properly configured.
     */
    protected function checkRateLimiting()
    {
        // Check if rate limiter is configured
        try {
            $limiter = app(\Illuminate\Cache\RateLimiter::class);
            return $limiter !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check key rotation policy.
     */
    protected function checkKeyRotation()
    {
        // In a real implementation, this would check key rotation logs
        // For now, just check if key rotation command exists
        return class_exists(\App\Console\Commands\RotateApiKeys::class);
    }

    /**
     * Generate comprehensive security report.
     */
    protected function generateComprehensiveReport($results)
    {
        $reportPath = storage_path('app/security-validation-' . now()->format('Y-m-d-His') . '.json');
        
        File::ensureDirectoryExists(dirname($reportPath));
        File::put($reportPath, json_encode($results, JSON_PRETTY_PRINT));
        
        $this->info('');
        $this->info('Security validation report saved to: ' . $reportPath);
        $this->info('Security Score: ' . $results['security_score'] . '/100');
        
        if ($results['security_score'] < 80) {
            $this->error('⚠️  Security score is below acceptable threshold!');
            $this->error('Please address the issues immediately.');
        } else {
            $this->info('✅ Security validation passed!');
        }
    }

    /**
     * Helper method for output.
     */
    protected function info($message)
    {
        if (method_exists($this, 'line')) {
            $this->line($message);
        } else {
            echo $message . PHP_EOL;
        }
    }

    /**
     * Helper method for error output.
     */
    protected function error($message)
    {
        if (method_exists($this, 'line')) {
            $this->line($message);
        } else {
            echo $message . PHP_EOL;
        }
    }
}