#!/usr/bin/env php
<?php

/**
 * Production Configuration Validator
 * 
 * This script validates all required environment variables and configuration
 * settings before production deployment. Run this before going live!
 * 
 * Usage: php scripts/validate-production-config.php
 */

// Color codes for terminal output
define('COLOR_RED', "\033[0;31m");
define('COLOR_GREEN', "\033[0;32m");
define('COLOR_YELLOW', "\033[0;33m");
define('COLOR_BLUE', "\033[0;34m");
define('COLOR_RESET', "\033[0m");

// Load the application bootstrap
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Validation class
class ProductionConfigValidator
{
    private array $errors = [];
    private array $warnings = [];
    private array $successes = [];
    private int $totalChecks = 0;
    
    /**
     * Run all validation checks
     */
    public function validate(): bool
    {
        $this->printHeader();
        
        // Core application settings
        $this->validateApplicationSettings();
        
        // Database connectivity
        $this->validateDatabaseConnection();
        
        // Redis connectivity
        $this->validateRedisConnection();
        
        // Stripe configuration
        $this->validateStripeConfiguration();
        
        // Billing configuration
        $this->validateBillingConfiguration();
        
        // Company information
        $this->validateCompanyInformation();
        
        // Third-party integrations
        $this->validateThirdPartyIntegrations();
        
        // Security settings
        $this->validateSecuritySettings();
        
        // File permissions
        $this->validateFilePermissions();
        
        // SSL/TLS configuration
        $this->validateSSLConfiguration();
        
        // Performance settings
        $this->validatePerformanceSettings();
        
        // Print results
        $this->printResults();
        
        return count($this->errors) === 0;
    }
    
    /**
     * Validate core application settings
     */
    private function validateApplicationSettings(): void
    {
        $this->section("Application Settings");
        
        // APP_ENV must be production
        if (env('APP_ENV') !== 'production') {
            $this->error("APP_ENV is not set to 'production' (current: " . env('APP_ENV') . ")");
        } else {
            $this->success("APP_ENV is set to production");
        }
        
        // APP_DEBUG must be false
        if (env('APP_DEBUG', true)) {
            $this->error("APP_DEBUG must be false in production");
        } else {
            $this->success("APP_DEBUG is disabled");
        }
        
        // APP_KEY must be set
        if (empty(env('APP_KEY'))) {
            $this->error("APP_KEY is not set - run: php artisan key:generate");
        } else {
            $this->success("APP_KEY is configured");
        }
        
        // APP_URL must use HTTPS
        $appUrl = env('APP_URL', '');
        if (!str_starts_with($appUrl, 'https://')) {
            $this->error("APP_URL must use HTTPS in production");
        } else {
            $this->success("APP_URL uses HTTPS");
        }
    }
    
    /**
     * Validate database connection
     */
    private function validateDatabaseConnection(): void
    {
        $this->section("Database Connection");
        
        try {
            DB::connection()->getPdo();
            $this->success("Database connection successful");
            
            // Check critical tables
            $criticalTables = [
                'tenants', 'users', 'transactions', 'balance_topups',
                'commission_ledgers', 'pricing_plans'
            ];
            
            foreach ($criticalTables as $table) {
                if (!Schema::hasTable($table)) {
                    $this->error("Critical table missing: {$table}");
                } else {
                    $this->success("Table exists: {$table}");
                }
            }
            
            // Check for pending migrations
            $pendingMigrations = app('migrator')->getMigrationFiles(
                app('migrator')->paths()
            );
            $ranMigrations = app('migrator')->getRepository()->getRan();
            $pending = array_diff(array_keys($pendingMigrations), $ranMigrations);
            
            if (count($pending) > 0) {
                $this->warning("Pending migrations: " . count($pending) . " - run: php artisan migrate");
            } else {
                $this->success("All migrations are up to date");
            }
            
        } catch (\Exception $e) {
            $this->error("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Validate Redis connection
     */
    private function validateRedisConnection(): void
    {
        $this->section("Redis Connection");
        
        try {
            $redis = Redis::connection();
            $redis->ping();
            $this->success("Redis connection successful");
            
            // Test cache operations
            Cache::put('config_test', 'test_value', 60);
            $value = Cache::get('config_test');
            
            if ($value === 'test_value') {
                $this->success("Redis cache operations working");
                Cache::forget('config_test');
            } else {
                $this->error("Redis cache operations failed");
            }
            
        } catch (\Exception $e) {
            $this->error("Redis connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Validate Stripe configuration
     */
    private function validateStripeConfiguration(): void
    {
        $this->section("Stripe Configuration");
        
        $stripeKey = env('STRIPE_KEY');
        $stripeSecret = env('STRIPE_SECRET');
        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');
        
        // Check if keys are set
        if (empty($stripeKey)) {
            $this->error("STRIPE_KEY is not set");
        } elseif (!str_starts_with($stripeKey, 'pk_live_')) {
            $this->warning("STRIPE_KEY does not appear to be a live key");
        } else {
            $this->success("STRIPE_KEY is configured (live)");
        }
        
        if (empty($stripeSecret)) {
            $this->error("STRIPE_SECRET is not set");
        } elseif (!str_starts_with($stripeSecret, 'sk_live_')) {
            $this->warning("STRIPE_SECRET does not appear to be a live key");
        } else {
            $this->success("STRIPE_SECRET is configured (live)");
        }
        
        if (empty($webhookSecret)) {
            $this->error("STRIPE_WEBHOOK_SECRET is not set");
        } elseif (!str_starts_with($webhookSecret, 'whsec_')) {
            $this->warning("STRIPE_WEBHOOK_SECRET format looks incorrect");
        } else {
            $this->success("STRIPE_WEBHOOK_SECRET is configured");
        }
        
        // Test Stripe API connection (if keys are set)
        if (!empty($stripeSecret)) {
            try {
                \Stripe\Stripe::setApiKey($stripeSecret);
                $account = \Stripe\Account::retrieve();
                $this->success("Stripe API connection successful (Account: {$account->email})");
            } catch (\Exception $e) {
                $this->error("Stripe API connection failed: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Validate billing configuration
     */
    private function validateBillingConfiguration(): void
    {
        $this->section("Billing Configuration");
        
        // Check if billing is enabled
        if (!env('BILLING_ENABLED', false)) {
            $this->warning("BILLING_ENABLED is not true");
        } else {
            $this->success("Billing system is enabled");
        }
        
        // Validate pricing configuration
        $prices = [
            'BILLING_PRICE_CALL_MINUTES' => 30,
            'BILLING_PRICE_API_CALLS' => 10,
            'BILLING_PRICE_APPOINTMENTS' => 100,
            'BILLING_PRICE_SMS_MESSAGES' => 5,
        ];
        
        foreach ($prices as $key => $defaultValue) {
            $value = env($key);
            if (is_null($value)) {
                $this->warning("{$key} not set, using default: {$defaultValue}");
            } elseif ($value <= 0) {
                $this->error("{$key} must be greater than 0");
            } else {
                $this->success("{$key} = {$value} cents");
            }
        }
        
        // Validate commission settings
        $commissionRate = env('BILLING_RESELLER_COMMISSION_RATE', 0.25);
        if ($commissionRate < 0 || $commissionRate > 1) {
            $this->error("BILLING_RESELLER_COMMISSION_RATE must be between 0 and 1");
        } else {
            $this->success("Reseller commission rate: " . ($commissionRate * 100) . "%");
        }
        
        // Validate topup limits
        $minTopup = env('BILLING_MIN_TOPUP_CENTS', 1000);
        $maxTopup = env('BILLING_MAX_TOPUP_CENTS', 100000);
        
        if ($minTopup >= $maxTopup) {
            $this->error("BILLING_MIN_TOPUP_CENTS must be less than BILLING_MAX_TOPUP_CENTS");
        } else {
            $this->success("Topup range: " . ($minTopup/100) . "€ - " . ($maxTopup/100) . "€");
        }
    }
    
    /**
     * Validate company information for invoices
     */
    private function validateCompanyInformation(): void
    {
        $this->section("Company Information");
        
        $requiredFields = [
            'COMPANY_NAME' => 'Company name',
            'COMPANY_ADDRESS' => 'Company address',
            'COMPANY_CITY' => 'Company city',
            'COMPANY_TAX_NUMBER' => 'Tax number',
            'COMPANY_VAT_ID' => 'VAT ID',
            'COMPANY_IBAN' => 'IBAN',
            'COMPANY_BIC' => 'BIC',
        ];
        
        foreach ($requiredFields as $key => $description) {
            if (empty(env($key))) {
                $this->error("{$description} ({$key}) is not set");
            } else {
                $this->success("{$description} is configured");
            }
        }
        
        // Validate IBAN format
        $iban = env('COMPANY_IBAN', '');
        if (!empty($iban) && !preg_match('/^DE\d{20}$/', str_replace(' ', '', $iban))) {
            $this->warning("COMPANY_IBAN format appears incorrect for German IBAN");
        }
        
        // Validate VAT ID format
        $vatId = env('COMPANY_VAT_ID', '');
        if (!empty($vatId) && !preg_match('/^DE\d{9}$/', $vatId)) {
            $this->warning("COMPANY_VAT_ID format appears incorrect for German VAT ID");
        }
    }
    
    /**
     * Validate third-party integrations
     */
    private function validateThirdPartyIntegrations(): void
    {
        $this->section("Third-Party Integrations");
        
        // Cal.com
        if (empty(env('CALCOM_API_KEY'))) {
            $this->warning("Cal.com API key not configured");
        } else {
            $this->success("Cal.com configured");
        }
        
        // Retell AI
        if (empty(env('RETELL_API_KEY'))) {
            $this->warning("Retell AI API key not configured");
        } else {
            $this->success("Retell AI configured");
        }
        
        // Twilio (SMS)
        if (empty(env('TWILIO_ACCOUNT_SID'))) {
            $this->warning("Twilio not configured (SMS disabled)");
        } else {
            $this->success("Twilio SMS configured");
        }
        
        // FCM (Push notifications)
        if (empty(env('FCM_SERVER_KEY'))) {
            $this->warning("FCM not configured (Push notifications disabled)");
        } else {
            $this->success("FCM Push notifications configured");
        }
    }
    
    /**
     * Validate security settings
     */
    private function validateSecuritySettings(): void
    {
        $this->section("Security Settings");
        
        // Check HTTPS enforcement
        if (!env('SESSION_SECURE_COOKIE', false)) {
            $this->error("SESSION_SECURE_COOKIE should be true for HTTPS");
        } else {
            $this->success("Secure cookies enabled");
        }
        
        // Check session encryption
        if (!env('SESSION_ENCRYPT', false)) {
            $this->warning("SESSION_ENCRYPT is not enabled");
        } else {
            $this->success("Session encryption enabled");
        }
        
        // Check CORS settings
        $corsOrigins = env('CORS_ALLOWED_ORIGINS', '');
        if (str_contains($corsOrigins, '*')) {
            $this->error("CORS_ALLOWED_ORIGINS should not use wildcard (*) in production");
        } else {
            $this->success("CORS properly configured");
        }
        
        // Check rate limiting
        $rateLimit = env('API_RATE_LIMIT', 60);
        if ($rateLimit > 100) {
            $this->warning("API_RATE_LIMIT is very high: {$rateLimit} requests/minute");
        } else {
            $this->success("API rate limiting: {$rateLimit} requests/minute");
        }
    }
    
    /**
     * Validate file permissions
     */
    private function validateFilePermissions(): void
    {
        $this->section("File Permissions");
        
        // Check .env file permissions
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $perms = substr(sprintf('%o', fileperms($envPath)), -4);
            if ($perms !== '0600' && $perms !== '0640') {
                $this->warning(".env file permissions are {$perms}, should be 0600 or 0640");
            } else {
                $this->success(".env file permissions are secure");
            }
        }
        
        // Check storage directory is writable
        $storagePath = storage_path();
        if (!is_writable($storagePath)) {
            $this->error("Storage directory is not writable");
        } else {
            $this->success("Storage directory is writable");
        }
        
        // Check bootstrap/cache is writable
        $cachePath = base_path('bootstrap/cache');
        if (!is_writable($cachePath)) {
            $this->error("Bootstrap cache directory is not writable");
        } else {
            $this->success("Bootstrap cache directory is writable");
        }
    }
    
    /**
     * Validate SSL/TLS configuration
     */
    private function validateSSLConfiguration(): void
    {
        $this->section("SSL/TLS Configuration");
        
        $appUrl = env('APP_URL', '');
        if (empty($appUrl)) {
            $this->error("APP_URL is not set");
            return;
        }
        
        $domain = parse_url($appUrl, PHP_URL_HOST);
        
        // Check if domain is reachable
        $headers = @get_headers($appUrl);
        if ($headers === false) {
            $this->warning("Cannot reach {$appUrl} - ensure DNS is configured");
        } else {
            $this->success("Domain {$domain} is reachable");
            
            // Check for HTTPS redirect
            if (!str_contains($headers[0], '200') && !str_contains($headers[0], '301')) {
                $this->warning("Unexpected response from {$appUrl}");
            }
        }
        
        // Check SSL certificate (basic check)
        if (str_starts_with($appUrl, 'https://')) {
            $context = stream_context_create([
                "ssl" => [
                    "capture_peer_cert" => true,
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ],
            ]);
            
            $stream = @stream_socket_client(
                "ssl://{$domain}:443",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            if ($stream) {
                $params = stream_context_get_params($stream);
                $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
                
                if ($cert) {
                    $validTo = $cert['validTo_time_t'];
                    $daysLeft = ($validTo - time()) / 86400;
                    
                    if ($daysLeft < 30) {
                        $this->warning("SSL certificate expires in {$daysLeft} days");
                    } else {
                        $this->success("SSL certificate valid for {$daysLeft} days");
                    }
                }
            } else {
                $this->warning("Could not verify SSL certificate");
            }
        }
    }
    
    /**
     * Validate performance settings
     */
    private function validatePerformanceSettings(): void
    {
        $this->section("Performance Settings");
        
        // Check opcache
        if (!function_exists('opcache_get_status')) {
            $this->warning("OPcache is not enabled");
        } else {
            $status = opcache_get_status();
            if ($status === false) {
                $this->warning("OPcache is installed but not active");
            } else {
                $this->success("OPcache is enabled");
            }
        }
        
        // Check queue configuration
        if (env('QUEUE_CONNECTION') === 'sync') {
            $this->error("QUEUE_CONNECTION should not be 'sync' in production");
        } else {
            $this->success("Queue driver: " . env('QUEUE_CONNECTION'));
        }
        
        // Check cache configuration
        if (env('CACHE_DRIVER') === 'array') {
            $this->error("CACHE_DRIVER should not be 'array' in production");
        } else {
            $this->success("Cache driver: " . env('CACHE_DRIVER'));
        }
        
        // Memory limit
        $memoryLimit = ini_get('memory_limit');
        $memoryBytes = $this->convertToBytes($memoryLimit);
        if ($memoryBytes < 256 * 1024 * 1024) {
            $this->warning("PHP memory_limit is low: {$memoryLimit}");
        } else {
            $this->success("PHP memory_limit: {$memoryLimit}");
        }
    }
    
    /**
     * Print section header
     */
    private function section(string $title): void
    {
        echo "\n" . COLOR_BLUE . "▶ {$title}" . COLOR_RESET . "\n";
        echo str_repeat("─", strlen($title) + 2) . "\n";
    }
    
    /**
     * Record error
     */
    private function error(string $message): void
    {
        $this->errors[] = $message;
        $this->totalChecks++;
        echo COLOR_RED . "  ✗ {$message}" . COLOR_RESET . "\n";
    }
    
    /**
     * Record warning
     */
    private function warning(string $message): void
    {
        $this->warnings[] = $message;
        $this->totalChecks++;
        echo COLOR_YELLOW . "  ⚠ {$message}" . COLOR_RESET . "\n";
    }
    
    /**
     * Record success
     */
    private function success(string $message): void
    {
        $this->successes[] = $message;
        $this->totalChecks++;
        echo COLOR_GREEN . "  ✓ {$message}" . COLOR_RESET . "\n";
    }
    
    /**
     * Print header
     */
    private function printHeader(): void
    {
        echo "\n";
        echo COLOR_BLUE . "╔════════════════════════════════════════════════════════════╗\n";
        echo "║         PRODUCTION CONFIGURATION VALIDATOR v2.0           ║\n";
        echo "║                  AskPro AI Billing System                 ║\n";
        echo "╚════════════════════════════════════════════════════════════╝" . COLOR_RESET . "\n";
        echo "\n";
        echo "Starting validation at: " . date('Y-m-d H:i:s') . "\n";
        echo "Environment: " . app()->environment() . "\n";
    }
    
    /**
     * Print results summary
     */
    private function printResults(): void
    {
        echo "\n";
        echo COLOR_BLUE . "═══════════════════════════════════════════════════════════════\n";
        echo "                        VALIDATION RESULTS                       \n";
        echo "═══════════════════════════════════════════════════════════════" . COLOR_RESET . "\n\n";
        
        $successCount = count($this->successes);
        $warningCount = count($this->warnings);
        $errorCount = count($this->errors);
        
        echo "Total checks performed: {$this->totalChecks}\n";
        echo COLOR_GREEN . "✓ Passed: {$successCount}" . COLOR_RESET . "\n";
        echo COLOR_YELLOW . "⚠ Warnings: {$warningCount}" . COLOR_RESET . "\n";
        echo COLOR_RED . "✗ Errors: {$errorCount}" . COLOR_RESET . "\n\n";
        
        if ($errorCount > 0) {
            echo COLOR_RED . "═══════════════════════════════════════════════════════════════\n";
            echo "                    ⚠️  CRITICAL ERRORS FOUND  ⚠️                \n";
            echo "═══════════════════════════════════════════════════════════════\n" . COLOR_RESET;
            echo "\nThe following critical errors must be fixed before deployment:\n\n";
            foreach ($this->errors as $i => $error) {
                echo COLOR_RED . ($i + 1) . ". {$error}" . COLOR_RESET . "\n";
            }
            echo "\n";
        }
        
        if ($warningCount > 0 && $errorCount === 0) {
            echo COLOR_YELLOW . "═══════════════════════════════════════════════════════════════\n";
            echo "                         WARNINGS FOUND                          \n";
            echo "═══════════════════════════════════════════════════════════════\n" . COLOR_RESET;
            echo "\nThe following warnings should be reviewed:\n\n";
            foreach ($this->warnings as $i => $warning) {
                echo COLOR_YELLOW . ($i + 1) . ". {$warning}" . COLOR_RESET . "\n";
            }
            echo "\n";
        }
        
        if ($errorCount === 0) {
            if ($warningCount === 0) {
                echo COLOR_GREEN . "╔════════════════════════════════════════════════════════════╗\n";
                echo "║            🎉 SYSTEM IS READY FOR PRODUCTION! 🎉          ║\n";
                echo "║                                                            ║\n";
                echo "║     All critical checks passed. You may deploy safely.    ║\n";
                echo "╚════════════════════════════════════════════════════════════╝" . COLOR_RESET . "\n";
            } else {
                echo COLOR_GREEN . "╔════════════════════════════════════════════════════════════╗\n";
                echo "║           ✅ SYSTEM CAN BE DEPLOYED (WITH CAUTION)        ║\n";
                echo "║                                                            ║\n";
                echo "║   No critical errors found, but please review warnings.   ║\n";
                echo "╚════════════════════════════════════════════════════════════╝" . COLOR_RESET . "\n";
            }
            
            echo "\n" . COLOR_BLUE . "Next steps:" . COLOR_RESET . "\n";
            echo "1. Configure Stripe webhook endpoint in dashboard\n";
            echo "2. Run database backup: php artisan backup:run\n";
            echo "3. Clear all caches: php artisan optimize:clear\n";
            echo "4. Cache configuration: php artisan optimize\n";
            echo "5. Start queue workers: php artisan queue:work\n";
            echo "6. Monitor logs: tail -f storage/logs/laravel.log\n";
        } else {
            echo COLOR_RED . "╔════════════════════════════════════════════════════════════╗\n";
            echo "║            ❌ SYSTEM IS NOT READY FOR PRODUCTION!         ║\n";
            echo "║                                                            ║\n";
            echo "║    Please fix all critical errors before deployment.      ║\n";
            echo "╚════════════════════════════════════════════════════════════╝" . COLOR_RESET . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Convert memory string to bytes
     */
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
}

// Run the validator
$validator = new ProductionConfigValidator();
$isValid = $validator->validate();

// Exit with appropriate code
exit($isValid ? 0 : 1);