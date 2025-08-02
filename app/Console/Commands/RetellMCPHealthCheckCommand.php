<?php

namespace App\Console\Commands;

use App\Services\MCP\RetellAIBridgeMCPServer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RetellMCPHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retell:mcp-health 
                            {--fix : Attempt to fix common issues}
                            {--detailed : Show detailed health information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check health status of Retell AI MCP Server integration';

    /**
     * Execute the console command.
     */
    public function handle(RetellAIBridgeMCPServer $bridgeServer): int
    {
        $this->info('🔍 Checking Retell AI MCP Server Health...');
        $this->newLine();

        $allHealthy = true;

        // 1. Check External MCP Server
        $this->info('1. External MCP Server Status:');
        $mcpHealth = $this->checkExternalMCPServer();

        if (! $mcpHealth['healthy']) {
            $allHealthy = false;
            if ($this->option('fix')) {
                $this->attemptMCPServerFix();
            }
        }

        // 2. Check Laravel Configuration
        $this->newLine();
        $this->info('2. Laravel Configuration:');
        $configHealth = $this->checkLaravelConfig();

        if (! $configHealth['healthy']) {
            $allHealthy = false;
        }

        // 3. Check Circuit Breaker Status
        $this->newLine();
        $this->info('3. Circuit Breaker Status:');
        $circuitBreakerHealth = $this->checkCircuitBreaker($bridgeServer);

        // 4. Check Database
        $this->newLine();
        $this->info('4. Database Status:');
        $dbHealth = $this->checkDatabase();

        // 5. Check Queue System
        $this->newLine();
        $this->info('5. Queue System:');
        $queueHealth = $this->checkQueueSystem();

        // Summary
        $this->newLine();
        $this->info('═══════════════════════════════════════');

        if ($allHealthy) {
            $this->info('✅ All systems operational!');

            return self::SUCCESS;
        } else {
            $this->error('❌ Some systems need attention.');

            if (! $this->option('fix')) {
                $this->warn('💡 Run with --fix flag to attempt automatic fixes.');
            }

            return self::FAILURE;
        }
    }

    /**
     * Check external MCP server health.
     */
    protected function checkExternalMCPServer(): array
    {
        $url = config('services.retell_mcp.url', 'http://localhost:3001');

        try {
            $response = Http::timeout(5)->get($url . '/health');

            if ($response->successful()) {
                $data = $response->json();
                $this->line("   ✅ Server is running at {$url}");

                if ($this->option('detailed')) {
                    $this->line('   📊 Status: ' . ($data['status'] ?? 'unknown'));
                    $this->line('   🔧 Service: ' . ($data['service'] ?? 'unknown'));
                    $this->line('   ⏰ Timestamp: ' . ($data['timestamp'] ?? 'unknown'));
                }

                return ['healthy' => true, 'data' => $data];
            } else {
                $this->error('   ❌ Server returned status: ' . $response->status());

                return ['healthy' => false, 'error' => 'Invalid status code'];
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Cannot connect to MCP server at {$url}");
            $this->error('   Error: ' . $e->getMessage());

            return ['healthy' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check Laravel configuration.
     */
    protected function checkLaravelConfig(): array
    {
        $healthy = true;

        // Check required config values
        $configs = [
            'services.retell_mcp.url' => 'MCP Server URL',
            'services.retell_mcp.token' => 'MCP Server Token',
            'retell-mcp.security.webhook_secret' => 'Webhook Secret',
        ];

        foreach ($configs as $key => $label) {
            $value = config($key);
            if (empty($value)) {
                $this->error("   ❌ Missing: {$label}");
                $healthy = false;
            } else {
                $this->line("   ✅ {$label}: " . (str_contains($key, 'token') || str_contains($key, 'secret') ? '[CONFIGURED]' : $value));
            }
        }

        // Check service provider registration
        if (app()->getProvider(\App\Providers\RetellAIMCPServiceProvider::class)) {
            $this->line('   ✅ Service Provider registered');
        } else {
            $this->error('   ❌ Service Provider not registered');
            $healthy = false;
        }

        return ['healthy' => $healthy];
    }

    /**
     * Check circuit breaker status.
     */
    protected function checkCircuitBreaker(RetellAIBridgeMCPServer $bridgeServer): array
    {
        try {
            $health = $bridgeServer->healthCheck();

            if (isset($health['circuit_breaker'])) {
                $cb = $health['circuit_breaker'];
                $status = $cb['status'] ?? 'unknown';

                switch ($status) {
                    case 'closed':
                        $this->line('   ✅ Circuit Breaker: CLOSED (healthy)');

                        break;
                    case 'open':
                        $this->error('   ❌ Circuit Breaker: OPEN (service unavailable)');
                        $this->line('   ⏰ Will retry at: ' . ($cb['will_retry_at'] ?? 'unknown'));

                        break;
                    case 'half-open':
                        $this->warn('   ⚠️  Circuit Breaker: HALF-OPEN (testing)');

                        break;
                }

                if ($this->option('detailed') && isset($cb['failures'])) {
                    $this->line('   📊 Failure count: ' . $cb['failures']);
                    $this->line('   🕐 Last failure: ' . ($cb['last_failure'] ?? 'none'));
                }

                return ['healthy' => $status === 'closed'];
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Could not check circuit breaker: ' . $e->getMessage());
        }

        return ['healthy' => false];
    }

    /**
     * Check database connectivity and migrations.
     */
    protected function checkDatabase(): array
    {
        try {
            // Check connection
            \DB::connection()->getPdo();
            $this->line('   ✅ Database connection successful');

            // Check if campaign table exists
            if (\Schema::hasTable('retell_ai_call_campaigns')) {
                $this->line('   ✅ Campaign table exists');

                if ($this->option('detailed')) {
                    $campaignCount = \App\Models\RetellAICallCampaign::count();
                    $this->line('   📊 Total campaigns: ' . $campaignCount);
                }
            } else {
                $this->error('   ❌ Campaign table missing - run migrations');

                return ['healthy' => false];
            }

            return ['healthy' => true];
        } catch (\Exception $e) {
            $this->error('   ❌ Database error: ' . $e->getMessage());

            return ['healthy' => false];
        }
    }

    /**
     * Check queue system.
     */
    protected function checkQueueSystem(): array
    {
        try {
            // Check if Horizon is running
            $horizonStatus = \Laravel\Horizon\Contracts\MasterSupervisorRepository::class;
            $masters = app($horizonStatus)->all();

            if (count($masters) > 0) {
                $this->line('   ✅ Horizon is running');

                if ($this->option('detailed')) {
                    foreach ($masters as $master) {
                        $this->line('   📊 Supervisor: ' . $master->name . ' (' . $master->status . ')');
                    }
                }
            } else {
                $this->warn('   ⚠️  Horizon is not running');
                $this->line('   💡 Start with: php artisan horizon');
            }

            return ['healthy' => count($masters) > 0];
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Could not check Horizon status');

            return ['healthy' => false];
        }
    }

    /**
     * Attempt to fix MCP server issues.
     */
    protected function attemptMCPServerFix(): void
    {
        $this->newLine();
        $this->warn('🔧 Attempting to fix MCP Server...');

        // Check if npm is installed
        $npmVersion = shell_exec('npm --version 2>&1');
        if (! $npmVersion) {
            $this->error('   ❌ npm is not installed');

            return;
        }

        $mcpPath = base_path('mcp-external/retellai-mcp-server');

        // Check if directory exists
        if (! is_dir($mcpPath)) {
            $this->error("   ❌ MCP Server directory not found at: {$mcpPath}");
            $this->line('   💡 Run: php artisan vendor:publish --tag=retell-mcp-server');

            return;
        }

        // Check if node_modules exists
        if (! is_dir($mcpPath . '/node_modules')) {
            $this->warn('   📦 Installing npm dependencies...');
            $output = shell_exec("cd {$mcpPath} && npm install 2>&1");
            $this->line($output);
        }

        // Check if .env exists
        if (! file_exists($mcpPath . '/.env')) {
            if (file_exists($mcpPath . '/.env.example')) {
                $this->warn('   📄 Creating .env file...');
                copy($mcpPath . '/.env.example', $mcpPath . '/.env');
                $this->warn('   ⚠️  Please configure .env file with your API keys');
            }
        }

        // Try to start the server
        $this->info('   🚀 Starting MCP Server...');
        $this->line('   💡 Run in a separate terminal: cd ' . $mcpPath . ' && npm start');
    }
}
