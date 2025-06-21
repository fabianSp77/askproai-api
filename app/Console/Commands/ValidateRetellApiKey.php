<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Company;
use App\Services\MCP\MetricsCollector;

class ValidateRetellApiKey extends Command
{
    protected $signature = 'retell:validate-api-key {--company=1}';
    protected $description = 'Validate Retell API key and diagnose issues';

    public function handle()
    {
        $companyId = $this->option('company');
        $company = Company::find($companyId);
        
        if (!$company) {
            $this->error("Company not found");
            return 1;
        }
        
        $this->info("Validating Retell API key for: {$company->name}");
        
        // Get API key
        $apiKey = $company->retell_api_key ? decrypt($company->retell_api_key) : null;
        
        if (!$apiKey) {
            $this->error("No API key configured for this company");
            
            // Check if there's a default key in config
            $defaultKey = config('services.retell.api_key');
            if ($defaultKey) {
                $this->warn("Found default API key in config. Testing with that...");
                $apiKey = $defaultKey;
            } else {
                return 1;
            }
        }
        
        // Check key format
        $this->info("\nAPI Key Analysis:");
        $this->line("  Format: " . (str_starts_with($apiKey, 'key_') ? '✓ Valid' : '✗ Invalid'));
        $this->line("  Length: " . strlen($apiKey) . " characters");
        $this->line("  Type: " . $this->detectKeyType($apiKey));
        
        // Log to MCP metrics
        if (class_exists(MetricsCollector::class)) {
            app(MetricsCollector::class)->recordApiCall(
                'retell',
                'validate_api_key',
                'started',
                0
            );
        }
        
        // Test API endpoints
        $this->info("\nTesting API Endpoints:");
        
        $endpoints = [
            'Basic connectivity' => ['GET', 'https://api.retellai.com/'],
            'List agents' => ['GET', 'https://api.retellai.com/list-agents'],
            'List phone numbers' => ['GET', 'https://api.retellai.com/list-phone-numbers'],
        ];
        
        $allSuccess = true;
        $startTime = microtime(true);
        
        foreach ($endpoints as $name => $config) {
            [$method, $url] = $config;
            $this->line("\n  Testing: $name");
            $this->line("  Method: $method $url");
            
            try {
                $request = Http::timeout(10)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Accept' => 'application/json',
                        'User-Agent' => 'AskProAI-Validator/1.0'
                    ]);
                
                $response = $method === 'GET' ? $request->get($url) : $request->post($url);
                
                $this->line("  Status: " . $response->status());
                
                if ($response->successful()) {
                    $this->info("  ✓ Success");
                    
                    // Log success to MCP
                    if (class_exists(MetricsCollector::class)) {
                        app(MetricsCollector::class)->recordApiCall(
                            'retell',
                            str_replace(' ', '_', strtolower($name)),
                            'success',
                            round((microtime(true) - $startTime) * 1000)
                        );
                    }
                    
                    if ($name === 'List agents') {
                        $data = $response->json();
                        $count = is_array($data) ? count($data) : 0;
                        $this->line("  Found $count agents");
                    }
                } else {
                    $allSuccess = false;
                    $this->error("  ✗ Failed");
                    $this->line("  Response: " . substr($response->body(), 0, 200));
                    
                    // Log failure to MCP
                    if (class_exists(MetricsCollector::class)) {
                        app(MetricsCollector::class)->recordApiCall(
                            'retell',
                            str_replace(' ', '_', strtolower($name)),
                            'error',
                            round((microtime(true) - $startTime) * 1000)
                        );
                    }
                }
            } catch (\Exception $e) {
                $allSuccess = false;
                $this->error("  ✗ Exception: " . $e->getMessage());
                
                // Log exception to MCP
                if (class_exists(MetricsCollector::class)) {
                    app(MetricsCollector::class)->recordApiCall(
                        'retell',
                        str_replace(' ', '_', strtolower($name)),
                        'exception',
                        round((microtime(true) - $startTime) * 1000)
                    );
                }
            }
        }
        
        // Test with raw cURL for debugging
        $this->info("\n\nTesting with raw cURL (for debugging):");
        $ch = curl_init('https://api.retellai.com/list-agents');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        curl_close($ch);
        
        $this->line("  HTTP Code: $httpCode");
        if ($curlError) {
            $this->error("  cURL Error: $curlError");
        }
        if ($httpCode !== 200) {
            $this->line("  Response: " . substr($result, 0, 200));
        }
        
        // Recommendations
        $this->info("\n\nRecommendations:");
        if ($apiKey && str_starts_with($apiKey, 'key_')) {
            $this->warn("1. Your API key format appears valid");
            $this->warn("2. The 500 error suggests:");
            $this->line("   - API key might be invalid/expired");
            $this->line("   - Account might have issues (check Retell dashboard)");
            $this->line("   - Try generating a new API key");
            $this->warn("3. Next steps:");
            $this->line("   - Log into https://dashboard.retellai.com");
            $this->line("   - Check account status and billing");
            $this->line("   - Generate new API key if needed");
            $this->line("   - Check https://status.retellai.com for service issues");
        } else {
            $this->error("API key format is invalid. Expected format: key_xxxxx");
        }
        
        // Save validation result to database for MCP tracking
        if (class_exists(MetricsCollector::class)) {
            app(MetricsCollector::class)->recordApiCall(
                'retell',
                'validate_api_key',
                $allSuccess ? 'success' : 'failed',
                round((microtime(true) - $startTime) * 1000)
            );
        }
        
        // Update company validation status
        $company->update([
            'retell_api_validated_at' => $allSuccess ? now() : null,
            'retell_api_validation_error' => $allSuccess ? null : 'API key validation failed'
        ]);
        
        return $allSuccess ? 0 : 1;
    }
    
    private function detectKeyType(string $key): string
    {
        if (str_starts_with($key, 'key_test_')) {
            return 'Test Key';
        } elseif (str_starts_with($key, 'key_live_')) {
            return 'Live/Production Key';
        } elseif (str_starts_with($key, 'key_')) {
            return 'Standard Key';
        }
        return 'Unknown Format';
    }
}