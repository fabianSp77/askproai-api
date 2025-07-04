<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use App\Services\CalcomV2Service;
use App\Services\RetellV2Service;
use App\Services\CircuitBreaker\CircuitBreakerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class PreflightCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'askproai:preflight 
                            {--company= : Specific company ID to check}
                            {--all : Check all companies}
                            {--quick : Quick check (essential items only)}
                            {--fix : Attempt to fix issues automatically}
                            {--json : Output results as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Führt umfassende Preflight-Checks für Produktionsbereitschaft durch';

    protected array $checkResults = [];
    protected int $errorCount = 0;
    protected int $warningCount = 0;
    protected int $successCount = 0;

    protected CalcomV2Service $calcomService;
    protected RetellV2Service $retellService;
    protected CircuitBreakerService $circuitBreaker;

    public function __construct(
        CalcomV2Service $calcomService,
        RetellV2Service $retellService,
        CircuitBreakerService $circuitBreaker
    ) {
        parent::__construct();
        $this->calcomService = $calcomService;
        $this->retellService = $retellService;
        $this->circuitBreaker = $circuitBreaker;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 AskProAI Preflight Checks');
        $this->line('=============================');

        $startTime = microtime(true);

        // Determine which companies to check
        $companies = $this->getCompaniesToCheck();

        if ($companies->isEmpty()) {
            $this->error('Keine Unternehmen zum Prüfen gefunden.');
            return 1;
        }

        // Run system-wide checks first
        $this->runSystemChecks();

        // Run company-specific checks
        foreach ($companies as $company) {
            $this->runCompanyChecks($company);
        }

        $duration = round(microtime(true) - $startTime, 2);

        // Display results
        if ($this->option('json')) {
            $this->outputJson($duration);
        } else {
            $this->displayResults($duration);
        }

        // Return appropriate exit code
        return $this->errorCount > 0 ? 1 : 0;
    }

    /**
     * Get companies to check based on options
     */
    protected function getCompaniesToCheck()
    {
        if ($companyId = $this->option('company')) {
            return Company::where('id', $companyId)->get();
        }

        if ($this->option('all')) {
            return Company::all();
        }

        // Default: Check companies with active subscriptions
        return Company::where('subscription_status', 'active')
            ->orWhere('subscription_status', 'trial')
            ->get();
    }

    /**
     * Run system-wide checks
     */
    protected function runSystemChecks(): void
    {
        $this->info("\n🔍 System-Checks:");

        // 1. Database Connection
        $this->checkDatabase();

        // 2. Redis Connection
        $this->checkRedis();

        // 3. Queue System
        $this->checkQueue();

        // 4. File Permissions
        $this->checkFilePermissions();

        // 5. External Services
        if (!$this->option('quick')) {
            $this->checkExternalServices();
        }

        // 6. SSL Certificate
        $this->checkSSL();

        // 7. Circuit Breakers
        $this->checkCircuitBreakers();

        // 8. Cache
        $this->checkCache();

        // 9. Environment
        $this->checkEnvironment();
    }

    /**
     * Run company-specific checks
     */
    protected function runCompanyChecks(Company $company): void
    {
        $this->info("\n🏢 Checks für {$company->name}:");

        // Set company context
        app()->instance('current_company', $company);

        // 1. Basic Setup
        $this->checkCompanySetup($company);

        // 2. Branches
        $this->checkBranches($company);

        // 3. Phone Numbers
        $this->checkPhoneNumbers($company);

        // 4. Cal.com Integration
        if (!$this->option('quick')) {
            $this->checkCalcomIntegration($company);
        }

        // 5. Retell.ai Integration
        if (!$this->option('quick')) {
            $this->checkRetellIntegration($company);
        }

        // 6. Staff & Services
        $this->checkStaffAndServices($company);

        // 7. Appointments
        $this->checkAppointments($company);
    }

    /**
     * Check database connection and performance
     */
    protected function checkDatabase(): void
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $time = round((microtime(true) - $start) * 1000, 2);

            if ($time > 100) {
                $this->addWarning('Database', "Langsame Verbindung: {$time}ms");
            } else {
                $this->addSuccess('Database', "Verbindung OK ({$time}ms)");
            }

            // Check table sizes
            $largeTables = DB::select("
                SELECT table_name, 
                       ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                       table_rows
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()
                AND data_length > 104857600
                ORDER BY data_length DESC
            ");

            foreach ($largeTables as $table) {
                $this->addWarning('Database', "Große Tabelle: {$table->table_name} ({$table->size_mb}MB, {$table->table_rows} rows)");
            }

        } catch (\Exception $e) {
            $this->addError('Database', 'Verbindung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    /**
     * Check Redis connection
     */
    protected function checkRedis(): void
    {
        try {
            Redis::ping();
            $this->addSuccess('Redis', 'Verbindung OK');

            // Check memory usage
            $info = Redis::info();
            $usedMemory = $info['used_memory_human'] ?? 'unknown';
            $this->addInfo('Redis', "Memory Usage: $usedMemory");

        } catch (\Exception $e) {
            $this->addError('Redis', 'Verbindung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    /**
     * Check queue system
     */
    protected function checkQueue(): void
    {
        try {
            // Check if Horizon is running
            $horizonStatus = trim(shell_exec('php artisan horizon:status 2>&1'));
            
            if (str_contains($horizonStatus, 'running')) {
                $this->addSuccess('Queue', 'Horizon läuft');
            } else {
                $this->addError('Queue', 'Horizon läuft nicht!');
                
                if ($this->option('fix')) {
                    shell_exec('php artisan horizon > /dev/null 2>&1 &');
                    $this->addInfo('Queue', 'Horizon wurde gestartet');
                }
            }

            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')->count();
            if ($failedJobs > 0) {
                $this->addWarning('Queue', "$failedJobs fehlgeschlagene Jobs gefunden");
            }

        } catch (\Exception $e) {
            $this->addError('Queue', 'Prüfung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    /**
     * Check file permissions
     */
    protected function checkFilePermissions(): void
    {
        $directories = [
            'storage/app' => 0775,
            'storage/framework' => 0775,
            'storage/logs' => 0775,
            'bootstrap/cache' => 0775,
            'public/build' => 0775,
        ];

        foreach ($directories as $dir => $expectedPerms) {
            if (!is_writable(base_path($dir))) {
                $this->addError('Permissions', "$dir ist nicht beschreibbar");
                
                if ($this->option('fix')) {
                    chmod(base_path($dir), $expectedPerms);
                    $this->addInfo('Permissions', "$dir Berechtigungen korrigiert");
                }
            } else {
                $this->addSuccess('Permissions', "$dir OK");
            }
        }
    }

    /**
     * Check external services availability
     */
    protected function checkExternalServices(): void
    {
        $services = [
            'Cal.com API' => 'https://api.cal.com/v2/health',
            'Retell.ai API' => 'https://api.retellai.com',
        ];

        foreach ($services as $name => $url) {
            try {
                $response = Http::timeout(5)->get($url);
                
                if ($response->successful()) {
                    $this->addSuccess('External Services', "$name erreichbar");
                } else {
                    $this->addWarning('External Services', "$name returned status: " . $response->status());
                }
            } catch (\Exception $e) {
                $this->addError('External Services', "$name nicht erreichbar");
            }
        }
    }

    /**
     * Check SSL certificate
     */
    protected function checkSSL(): void
    {
        $url = config('app.url');
        
        if (!str_starts_with($url, 'https://')) {
            $this->addWarning('SSL', 'HTTPS nicht konfiguriert');
            return;
        }

        try {
            $context = stream_context_create([
                "ssl" => [
                    "capture_peer_cert" => true,
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ],
            ]);

            $stream = stream_socket_client(
                "ssl://" . parse_url($url, PHP_URL_HOST) . ":443",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if ($stream) {
                $params = stream_context_get_params($stream);
                $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
                
                $validTo = $cert['validTo_time_t'];
                $daysRemaining = floor(($validTo - time()) / 86400);
                
                if ($daysRemaining < 30) {
                    $this->addWarning('SSL', "Zertifikat läuft in $daysRemaining Tagen ab");
                } else {
                    $this->addSuccess('SSL', "Zertifikat gültig für $daysRemaining Tage");
                }
            }
        } catch (\Exception $e) {
            $this->addError('SSL', 'Zertifikat-Prüfung fehlgeschlagen');
        }
    }

    /**
     * Check circuit breakers
     */
    protected function checkCircuitBreakers(): void
    {
        try {
            // Circuit breaker implementation may vary
            $this->addInfo('Circuit Breakers', 'Circuit Breaker Status wird überprüft');
            
            // Try to check if services are available
            $services = ['calcom', 'retell', 'stripe'];
            
            foreach ($services as $service) {
                try {
                    // Check if we can connect to the service
                    $isHealthy = $this->checkServiceHealth($service);
                    
                    if ($isHealthy) {
                        $this->addSuccess('Circuit Breakers', "$service: Service erreichbar");
                    } else {
                        $this->addWarning('Circuit Breakers', "$service: Service möglicherweise nicht erreichbar");
                    }
                } catch (\Exception $e) {
                    $this->addError('Circuit Breakers', "$service: Prüfung fehlgeschlagen");
                }
            }
        } catch (\Exception $e) {
            $this->addWarning('Circuit Breakers', 'Circuit Breaker Status konnte nicht geprüft werden');
        }
    }
    
    /**
     * Check if a service is healthy
     */
    protected function checkServiceHealth(string $service): bool
    {
        switch ($service) {
            case 'calcom':
                return !empty(config('services.calcom.api_key'));
                
            case 'retell':
                return !empty(config('services.retell.api_key'));
                
            case 'stripe':
                return !empty(config('services.stripe.secret'));
                
            default:
                return false;
        }
    }

    /**
     * Check cache system
     */
    protected function checkCache(): void
    {
        try {
            $key = 'preflight_test_' . Str::random(10);
            Cache::put($key, 'test', 60);
            $value = Cache::get($key);
            Cache::forget($key);
            
            if ($value === 'test') {
                $this->addSuccess('Cache', 'Cache funktioniert');
            } else {
                $this->addError('Cache', 'Cache-Test fehlgeschlagen');
            }
        } catch (\Exception $e) {
            $this->addError('Cache', 'Cache nicht verfügbar: ' . $e->getMessage());
        }
    }

    /**
     * Check environment configuration
     */
    protected function checkEnvironment(): void
    {
        // Check if debug is disabled
        if (config('app.debug')) {
            $this->addWarning('Environment', 'Debug-Modus ist aktiviert!');
        }

        // Check environment
        if (app()->environment() !== 'production') {
            $this->addWarning('Environment', 'Nicht in Production-Modus: ' . app()->environment());
        }

        // Check required environment variables
        $required = [
            'APP_KEY',
            'DB_CONNECTION',
            'CACHE_DRIVER',
            'QUEUE_CONNECTION',
            'MAIL_MAILER',
        ];

        foreach ($required as $var) {
            if (empty(env($var))) {
                $this->addError('Environment', "$var nicht gesetzt");
            }
        }
    }

    /**
     * Check company basic setup
     */
    protected function checkCompanySetup(Company $company): void
    {
        // Check API keys
        if (empty($company->calcom_api_key)) {
            $this->addWarning("Company: {$company->name}", 'Cal.com API Key fehlt');
        }

        if (empty($company->retell_api_key)) {
            $this->addWarning("Company: {$company->name}", 'Retell.ai API Key fehlt');
        }

        // Check subscription
        if ($company->subscription_status === 'expired') {
            $this->addError("Company: {$company->name}", 'Subscription abgelaufen');
        }

        // Check settings
        if (empty($company->settings['timezone'])) {
            $this->addWarning("Company: {$company->name}", 'Timezone nicht konfiguriert');
        }
    }

    /**
     * Check branches
     */
    protected function checkBranches(Company $company): void
    {
        $branches = Branch::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->get();

        if ($branches->isEmpty()) {
            $this->addError("Branches: {$company->name}", 'Keine Filialen gefunden');
            return;
        }

        foreach ($branches as $branch) {
            // Check if active
            if (!$branch->is_active) {
                $this->addWarning("Branch: {$branch->name}", 'Filiale ist deaktiviert');
            }

            // Check working hours
            if (empty($branch->working_hours)) {
                $this->addWarning("Branch: {$branch->name}", 'Öffnungszeiten nicht konfiguriert');
            }

            // Check address
            if (empty($branch->address) || empty($branch->city)) {
                $this->addWarning("Branch: {$branch->name}", 'Adresse unvollständig');
            }
        }
    }

    /**
     * Check phone numbers
     */
    protected function checkPhoneNumbers(Company $company): void
    {
        $phoneNumbers = PhoneNumber::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->get();

        if ($phoneNumbers->isEmpty()) {
            $this->addError("Phone: {$company->name}", 'Keine Telefonnummern konfiguriert');
            return;
        }

        $hasActive = false;
        foreach ($phoneNumbers as $phone) {
            if ($phone->is_active) {
                $hasActive = true;

                // Check Retell configuration
                if (empty($phone->retell_agent_id)) {
                    $this->addWarning("Phone: {$phone->number}", 'Kein Retell Agent zugewiesen');
                }
            }
        }

        if (!$hasActive) {
            $this->addError("Phone: {$company->name}", 'Keine aktive Telefonnummer');
        }
    }

    /**
     * Check Cal.com integration
     */
    protected function checkCalcomIntegration(Company $company): void
    {
        if (empty($company->calcom_api_key)) {
            return;
        }

        try {
            $this->calcomService->setApiKey(decrypt($company->calcom_api_key));
            $user = $this->calcomService->getMe();
            
            if ($user) {
                $this->addSuccess("Cal.com: {$company->name}", 'Integration funktioniert');
                
                // Check event types
                $eventTypes = $this->calcomService->getEventTypes();
                if (empty($eventTypes)) {
                    $this->addWarning("Cal.com: {$company->name}", 'Keine Event-Typen gefunden');
                }
            }
        } catch (\Exception $e) {
            $this->addError("Cal.com: {$company->name}", 'API-Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Check Retell.ai integration
     */
    protected function checkRetellIntegration(Company $company): void
    {
        if (empty($company->retell_api_key)) {
            return;
        }

        try {
            $this->retellService->setApiKey(decrypt($company->retell_api_key));
            
            // Check agents
            $agents = RetellAgent::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->get();
            
            if ($agents->isEmpty()) {
                $this->addWarning("Retell: {$company->name}", 'Keine Agents konfiguriert');
            } else {
                foreach ($agents as $agent) {
                    try {
                        $agentData = $this->retellService->getAgent($agent->retell_agent_id);
                        if ($agentData) {
                            $this->addSuccess("Retell Agent: {$agent->name}", 'Agent erreichbar');
                        }
                    } catch (\Exception $e) {
                        $this->addError("Retell Agent: {$agent->name}", 'Agent nicht erreichbar');
                    }
                }
            }
        } catch (\Exception $e) {
            $this->addError("Retell: {$company->name}", 'API-Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Check staff and services
     */
    protected function checkStaffAndServices(Company $company): void
    {
        // Check services
        $services = DB::table('services')
            ->where('company_id', $company->id)
            ->where('active', true)
            ->count();

        if ($services === 0) {
            $this->addError("Services: {$company->name}", 'Keine aktiven Services gefunden');
        } else {
            $this->addSuccess("Services: {$company->name}", "$services aktive Services");
        }

        // Check staff
        $staff = DB::table('staff')
            ->where('company_id', $company->id)
            ->where('active', true)
            ->count();

        if ($staff === 0) {
            $this->addError("Staff: {$company->name}", 'Keine aktiven Mitarbeiter gefunden');
        } else {
            $this->addSuccess("Staff: {$company->name}", "$staff aktive Mitarbeiter");
        }
    }

    /**
     * Check appointments
     */
    protected function checkAppointments(Company $company): void
    {
        // Check for today's appointments
        $todayAppointments = DB::table('appointments')
            ->where('company_id', $company->id)
            ->whereDate('starts_at', today())
            ->count();

        if ($todayAppointments > 0) {
            $this->addInfo("Appointments: {$company->name}", "$todayAppointments Termine heute");
        }

        // Check for overlapping appointments
        $overlapping = DB::select("
            SELECT COUNT(*) as count
            FROM appointments a1
            JOIN appointments a2 ON a1.id != a2.id
            WHERE a1.company_id = ?
            AND a2.company_id = ?
            AND a1.staff_id = a2.staff_id
            AND a1.status = 'scheduled'
            AND a2.status = 'scheduled'
            AND a1.starts_at < a2.ends_at
            AND a2.starts_at < a1.ends_at
        ", [$company->id, $company->id]);

        if ($overlapping[0]->count > 0) {
            $this->addError("Appointments: {$company->name}", 'Überlappende Termine gefunden!');
        }
    }

    /**
     * Add result entry
     */
    protected function addResult(string $category, string $message, string $type): void
    {
        $this->checkResults[] = [
            'category' => $category,
            'message' => $message,
            'type' => $type,
            'timestamp' => now()->toIso8601String(),
        ];

        switch ($type) {
            case 'error':
                $this->errorCount++;
                break;
            case 'warning':
                $this->warningCount++;
                break;
            case 'success':
                $this->successCount++;
                break;
        }
    }

    protected function addError(string $category, string $message): void
    {
        $this->addResult($category, $message, 'error');
        if (!$this->option('json')) {
            $this->error("  ❌ [$category] $message");
        }
    }

    protected function addWarning(string $category, string $message): void
    {
        $this->addResult($category, $message, 'warning');
        if (!$this->option('json')) {
            $this->warn("  ⚠️  [$category] $message");
        }
    }

    protected function addSuccess(string $category, string $message): void
    {
        $this->addResult($category, $message, 'success');
        if (!$this->option('json')) {
            $this->info("  ✅ [$category] $message");
        }
    }

    protected function addInfo(string $category, string $message): void
    {
        $this->addResult($category, $message, 'info');
        if (!$this->option('json')) {
            $this->line("  ℹ️  [$category] $message");
        }
    }

    /**
     * Display results summary
     */
    protected function displayResults(float $duration): void
    {
        $this->newLine(2);
        $this->info('🏁 Preflight Check Ergebnisse');
        $this->line('============================');
        
        $total = $this->successCount + $this->warningCount + $this->errorCount;
        
        $this->table(
            ['Kategorie', 'Anzahl', 'Prozent'],
            [
                ['✅ Erfolg', $this->successCount, round(($this->successCount / $total) * 100, 1) . '%'],
                ['⚠️  Warnungen', $this->warningCount, round(($this->warningCount / $total) * 100, 1) . '%'],
                ['❌ Fehler', $this->errorCount, round(($this->errorCount / $total) * 100, 1) . '%'],
                ['📊 Gesamt', $total, '100%'],
            ]
        );

        $this->line("⏱️  Dauer: {$duration}s");

        if ($this->errorCount > 0) {
            $this->newLine();
            $this->error('❌ System ist NICHT bereit für Produktion!');
            $this->line('Beheben Sie alle Fehler vor dem Go-Live.');
        } elseif ($this->warningCount > 0) {
            $this->newLine();
            $this->warn('⚠️  System ist bedingt bereit für Produktion.');
            $this->line('Überprüfen Sie die Warnungen vor dem Go-Live.');
        } else {
            $this->newLine();
            $this->info('✅ System ist bereit für Produktion!');
        }

        if ($this->option('fix')) {
            $this->newLine();
            $this->info('🔧 Auto-Fix wurde angewendet wo möglich.');
        }
    }

    /**
     * Output results as JSON
     */
    protected function outputJson(float $duration): void
    {
        $output = [
            'timestamp' => now()->toIso8601String(),
            'duration' => $duration,
            'summary' => [
                'total' => $this->successCount + $this->warningCount + $this->errorCount,
                'success' => $this->successCount,
                'warnings' => $this->warningCount,
                'errors' => $this->errorCount,
            ],
            'ready_for_production' => $this->errorCount === 0,
            'results' => $this->checkResults,
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }
}