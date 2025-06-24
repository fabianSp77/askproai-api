<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;
use App\Services\Security\ApiKeyEncryptionService;

class SecurityAudit extends Command
{
    protected $signature = 'security:audit 
                            {--fix : Automatically fix issues where possible}
                            {--detailed : Show detailed security information}';
    
    protected $description = 'Run comprehensive security audit on the application';
    
    private array $issues = [];
    private ApiKeyEncryptionService $encryptionService;
    
    public function __construct(ApiKeyEncryptionService $encryptionService)
    {
        parent::__construct();
        $this->encryptionService = $encryptionService;
    }
    
    public function handle()
    {
        $this->info('=== AskProAI Security Audit ===');
        $this->info('Starting at: ' . now()->format('Y-m-d H:i:s'));
        $this->newLine();
        
        // Run security checks
        $this->checkApiKeyEncryption();
        $this->checkEnvironmentSecurity();
        $this->checkDatabaseSecurity();
        $this->checkFileSecurity();
        $this->checkWebhookSecurity();
        $this->checkSessionSecurity();
        
        // Display results
        $this->displayResults();
        
        // Fix issues if requested
        if ($this->option('fix')) {
            $this->fixIssues();
        }
        
        return count($this->issues) === 0 ? Command::SUCCESS : Command::FAILURE;
    }
    
    private function checkApiKeyEncryption()
    {
        $this->info('Checking API key encryption...');
        
        // Check companies table
        $plainTextKeys = DB::table('companies')
            ->where(function($query) {
                $query->whereNotNull('calcom_api_key')
                      ->whereRaw("calcom_api_key NOT LIKE 'eyJ%'");
            })
            ->orWhere(function($query) {
                $query->whereNotNull('retell_api_key')
                      ->whereRaw("retell_api_key NOT LIKE 'eyJ%'");
            })
            ->count();
        
        if ($plainTextKeys > 0) {
            $this->issues[] = [
                'severity' => 'critical',
                'category' => 'API Keys',
                'issue' => "Found {$plainTextKeys} companies with unencrypted API keys",
                'fix' => 'Run: php artisan security:rotate-keys --encrypt-only'
            ];
        }
        
        // Check retell_configurations table
        if (Schema::hasTable('retell_configurations')) {
            $plainWebhooks = DB::table('retell_configurations')
                ->whereNotNull('webhook_secret')
                ->whereRaw("webhook_secret NOT LIKE 'eyJ%'")
                ->count();
                
            if ($plainWebhooks > 0) {
                $this->issues[] = [
                    'severity' => 'critical',
                    'category' => 'Webhook Secrets',
                    'issue' => "Found {$plainWebhooks} unencrypted webhook secrets",
                    'fix' => 'Run: php artisan security:rotate-keys --encrypt-only'
                ];
            }
        }
    }
    
    private function checkEnvironmentSecurity()
    {
        $this->info('Checking environment security...');
        
        // Check debug mode
        if (config('app.debug') === true) {
            $this->issues[] = [
                'severity' => 'high',
                'category' => 'Environment',
                'issue' => 'Debug mode is enabled in production',
                'fix' => 'Set APP_DEBUG=false in .env'
            ];
        }
        
        // Check .env permissions
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $permissions = substr(sprintf('%o', fileperms($envPath)), -4);
            if ($permissions !== '0600') {
                $this->issues[] = [
                    'severity' => 'high',
                    'category' => 'File Permissions',
                    'issue' => ".env file has insecure permissions: {$permissions}",
                    'fix' => 'Run: chmod 600 .env'
                ];
            }
        }
        
        // Check for default APP_KEY
        if (config('app.key') === 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=') {
            $this->issues[] = [
                'severity' => 'critical',
                'category' => 'Encryption',
                'issue' => 'Using default APP_KEY',
                'fix' => 'Run: php artisan key:generate'
            ];
        }
    }
    
    private function checkDatabaseSecurity()
    {
        $this->info('Checking database security...');
        
        // Check for SQL injection vulnerabilities
        $riskyFiles = [
            'app/Services/RealTime/IntelligentCallRouter.php',
            'app/Services/RealTime/ConcurrentCallManager.php',
            'app/Services/QueryOptimizer.php'
        ];
        
        foreach ($riskyFiles as $file) {
            $path = base_path($file);
            if (File::exists($path)) {
                $content = File::get($path);
                if (preg_match('/whereRaw\s*\([^)]*\$/', $content)) {
                    $this->issues[] = [
                        'severity' => 'medium',
                        'category' => 'SQL Injection',
                        'issue' => "Potential SQL injection in {$file}",
                        'fix' => 'Review and parameterize all whereRaw queries'
                    ];
                }
            }
        }
        
        // Check for missing indexes
        $tables = ['calls', 'appointments', 'customers'];
        foreach ($tables as $table) {
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            $indexedColumns = array_column($indexes, 'Column_name');
            
            if (!in_array('company_id', $indexedColumns)) {
                $this->issues[] = [
                    'severity' => 'medium',
                    'category' => 'Performance',
                    'issue' => "Missing index on {$table}.company_id",
                    'fix' => "Add index: CREATE INDEX idx_{$table}_company_id ON {$table}(company_id)"
                ];
            }
        }
    }
    
    private function checkFileSecurity()
    {
        $this->info('Checking file security...');
        
        // Check storage permissions
        $storagePath = storage_path();
        $storagePerms = substr(sprintf('%o', fileperms($storagePath)), -4);
        if ($storagePerms !== '0755') {
            $this->issues[] = [
                'severity' => 'low',
                'category' => 'File Permissions',
                'issue' => "Storage directory has permissions: {$storagePerms}",
                'fix' => 'Run: chmod 755 storage'
            ];
        }
        
        // Check for exposed files
        $sensitiveFiles = [
            '.git/config',
            'composer.json',
            'phpunit.xml',
            '.env.example'
        ];
        
        foreach ($sensitiveFiles as $file) {
            $path = public_path($file);
            if (File::exists($path)) {
                $this->issues[] = [
                    'severity' => 'high',
                    'category' => 'Exposed Files',
                    'issue' => "Sensitive file accessible: public/{$file}",
                    'fix' => "Remove file from public directory"
                ];
            }
        }
    }
    
    private function checkWebhookSecurity()
    {
        $this->info('Checking webhook security...');
        
        // Check webhook routes for signature verification
        $webhookRoutes = [
            'retell.webhook' => 'verify.retell.signature',
            'calcom.webhook' => 'calcom.signature',
            'stripe.webhook' => 'verify.stripe.signature'
        ];
        
        foreach ($webhookRoutes as $route => $middleware) {
            try {
                $routeObj = app('router')->getRoutes()->getByName($route);
                if ($routeObj) {
                    $middlewares = $routeObj->gatherMiddleware();
                    if (!in_array($middleware, $middlewares)) {
                        $this->issues[] = [
                            'severity' => 'critical',
                            'category' => 'Webhook Security',
                            'issue' => "Route {$route} missing signature verification",
                            'fix' => "Add middleware: {$middleware}"
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Route doesn't exist, skip
            }
        }
    }
    
    private function checkSessionSecurity()
    {
        $this->info('Checking session security...');
        
        // Check session configuration
        if (config('session.secure') !== true && config('app.env') === 'production') {
            $this->issues[] = [
                'severity' => 'medium',
                'category' => 'Session',
                'issue' => 'Sessions not configured for HTTPS only',
                'fix' => 'Set SESSION_SECURE_COOKIE=true in .env'
            ];
        }
        
        if (config('session.http_only') !== true) {
            $this->issues[] = [
                'severity' => 'medium',
                'category' => 'Session',
                'issue' => 'Session cookies accessible via JavaScript',
                'fix' => 'Set SESSION_HTTP_ONLY=true in .env'
            ];
        }
    }
    
    private function displayResults()
    {
        $this->newLine();
        $this->info('=== Security Audit Results ===');
        
        if (empty($this->issues)) {
            $this->info('✓ No security issues found!');
            return;
        }
        
        // Group by severity
        $grouped = collect($this->issues)->groupBy('severity');
        
        foreach (['critical', 'high', 'medium', 'low'] as $severity) {
            if (!isset($grouped[$severity])) {
                continue;
            }
            
            $count = count($grouped[$severity]);
            $color = match($severity) {
                'critical' => 'error',
                'high' => 'error',
                'medium' => 'warn',
                'low' => 'comment',
                default => 'info'
            };
            
            $this->newLine();
            $this->line("<{$color}>" . strtoupper($severity) . " ({$count} issues)</{$color}>");
            
            foreach ($grouped[$severity] as $issue) {
                $this->line("  [{$issue['category']}] {$issue['issue']}");
                if ($this->option('detailed')) {
                    $this->line("    Fix: {$issue['fix']}");
                }
            }
        }
        
        $this->newLine();
        $this->info('Total issues: ' . count($this->issues));
    }
    
    private function fixIssues()
    {
        $this->newLine();
        
        if (!$this->confirm('Attempt to fix issues automatically?')) {
            return;
        }
        
        $fixed = 0;
        
        foreach ($this->issues as $issue) {
            switch ($issue['category']) {
                case 'File Permissions':
                    if (str_contains($issue['fix'], 'chmod')) {
                        $this->info("Fixing: {$issue['issue']}");
                        system($issue['fix']);
                        $fixed++;
                    }
                    break;
                    
                case 'Environment':
                    if ($issue['issue'] === 'Debug mode is enabled in production') {
                        $this->info("Fixing: {$issue['issue']}");
                        $this->updateEnvValue('APP_DEBUG', 'false');
                        $fixed++;
                    }
                    break;
            }
        }
        
        $this->info("Fixed {$fixed} issues automatically.");
        $this->warn("Some issues require manual intervention.");
    }
    
    private function updateEnvValue($key, $value)
    {
        $path = base_path('.env');
        
        if (File::exists($path)) {
            $content = File::get($path);
            
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
            
            File::put($path, $content);
        }
    }
}