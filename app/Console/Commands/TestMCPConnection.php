<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Company;

class TestMCPConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:test {token?} {--endpoint=info}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test MCP server connection and endpoints';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = $this->argument('token');
        $endpoint = $this->option('endpoint');
        
        if (!$token) {
            $this->warn('No token provided. Please provide a token to test authenticated endpoints.');
            $this->info('Usage: php artisan mcp:test YOUR_TOKEN');
            return 1;
        }
        
        $baseUrl = config('app.url') . '/api/mcp';
        
        $this->info("🔍 Testing MCP Connection...");
        $this->info("Base URL: {$baseUrl}");
        $this->newLine();
        
        // Test endpoints
        $endpoints = [
            'info' => ['method' => 'GET', 'path' => '/info'],
            'database-schema' => ['method' => 'GET', 'path' => '/database/schema'],
            'database-stats' => ['method' => 'GET', 'path' => '/database/call-stats?days=7'],
            'sentry-issues' => ['method' => 'GET', 'path' => '/sentry/issues?limit=5'],
        ];
        
        if ($endpoint !== 'all' && isset($endpoints[$endpoint])) {
            $this->testEndpoint($baseUrl, $token, $endpoint, $endpoints[$endpoint]);
        } else {
            foreach ($endpoints as $name => $config) {
                $this->testEndpoint($baseUrl, $token, $name, $config);
                $this->newLine();
            }
        }
        
        // Test company-specific endpoints if we have companies
        $company = Company::first();
        if ($company) {
            $this->info("📊 Testing Company-Specific Endpoints (Company: {$company->name})");
            $this->newLine();
            
            $companyEndpoints = [
                'calcom-test' => ['method' => 'GET', 'path' => "/calcom/test/{$company->id}"],
                'retell-test' => ['method' => 'GET', 'path' => "/retell/test/{$company->id}"],
                'tenant-stats' => ['method' => 'GET', 'path' => "/database/tenant-stats?company_id={$company->id}"],
            ];
            
            foreach ($companyEndpoints as $name => $config) {
                $this->testEndpoint($baseUrl, $token, $name, $config);
                $this->newLine();
            }
        }
        
        $this->info("✅ MCP Connection test completed!");
        
        return 0;
    }
    
    private function testEndpoint($baseUrl, $token, $name, $config)
    {
        $this->info("Testing: {$name}");
        $this->line("Endpoint: {$config['method']} {$config['path']}");
        
        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->{strtolower($config['method'])}($baseUrl . $config['path']);
            
            if ($response->successful()) {
                $this->info("✅ Success! Status: {$response->status()}");
                
                $data = $response->json();
                if (is_array($data)) {
                    $this->line("Response preview:");
                    $preview = array_slice($data, 0, 3);
                    foreach ($preview as $key => $value) {
                        if (is_array($value)) {
                            $this->line("  {$key}: [array with " . count($value) . " items]");
                        } else {
                            $this->line("  {$key}: " . (is_string($value) ? $value : json_encode($value)));
                        }
                    }
                }
            } else {
                $this->error("❌ Failed! Status: {$response->status()}");
                $this->line("Response: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
        }
    }
}