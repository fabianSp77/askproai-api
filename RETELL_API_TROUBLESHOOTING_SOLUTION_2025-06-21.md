# Retell API Troubleshooting Solution Guide
## Date: 2025-06-21

## Problem Analysis
Based on my research, the 500 Internal Server Error from Retell API can have several causes.

## Key Findings from Research

### 1. **Retell Status Page** ✅
- URL: https://status.retellai.com/
- Current Status: **All Systems Operational**
- API Uptime: **100%**
- Recent incidents were minor and resolved

### 2. **API Key Requirements**
From official documentation:
- Format: `Authorization: Bearer YOUR_API_KEY`
- Each workspace can have multiple API keys
- One key is designated for webhook authentication
- Keys must be kept secure (backend only)

### 3. **Automatic Retry Mechanism**
Retell SDK automatically retries on:
- 500 Internal Server Errors
- Network connectivity issues
- 408, 409, 429 errors
- Default: 2 retries with exponential backoff

### 4. **Common 500 Error Causes**
1. **Invalid API Key** - Most common cause
2. **Workspace Issues** - Key belongs to different workspace
3. **Rate Limiting** - Though usually returns 429
4. **Account Status** - Suspended or payment issues

## Immediate Solutions to Implement

### Solution 1: Enhanced Error Logging and Debugging

```php
// Enable detailed logging for Retell
putenv('RETELL_LOG=debug');
```

### Solution 2: Create Test Script with Enhanced Debugging

```php
<?php
// test-retell-debug.php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use Illuminate\Support\Facades\Http;

echo "RETELL API DEBUGGING\n";
echo str_repeat('=', 50) . "\n\n";

// Enable debug logging
putenv('RETELL_LOG=debug');

$company = Company::find(1);
$apiKey = $company->retell_api_key ? decrypt($company->retell_api_key) : config('services.retell.api_key');

// Test 1: Basic connectivity test
echo "1. Testing basic API connectivity...\n";
$response = Http::timeout(10)
    ->withHeaders([
        'User-Agent' => 'AskProAI/1.0',
        'Accept' => 'application/json'
    ])
    ->get('https://api.retellai.com/');
echo "   Status: " . $response->status() . "\n";

// Test 2: Test with exact header format from docs
echo "\n2. Testing with exact documentation format...\n";
$response = Http::timeout(10)
    ->withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'User-Agent' => 'AskProAI/1.0'
    ])
    ->get('https://api.retellai.com/list-agents');

echo "   Status: " . $response->status() . "\n";
echo "   Headers sent:\n";
foreach ($response->handlerStats()['request_headers'] ?? [] as $header) {
    echo "     " . $header . "\n";
}

if (!$response->successful()) {
    echo "   Response body: " . $response->body() . "\n";
    
    // Check response headers for clues
    echo "   Response headers:\n";
    foreach ($response->headers() as $key => $values) {
        echo "     $key: " . implode(', ', $values) . "\n";
    }
}

// Test 3: Try with curl to bypass any Laravel HTTP client issues
echo "\n3. Testing with raw cURL...\n";
$ch = curl_init('https://api.retellai.com/list-agents');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_STDERR, fopen('php://output', 'w'));

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "\n   HTTP Code: $httpCode\n";
if ($httpCode !== 200) {
    echo "   Response: $result\n";
}
curl_close($ch);
```

### Solution 3: Implement MCP-based Fallback System

```php
// app/Services/MCP/RetellMCPServer.php - Add this method

public function handleApiFailure(string $operation, \Exception $e): array
{
    // Log to MCP monitoring
    DB::table('mcp_metrics')->insert([
        'service' => 'retell',
        'operation' => $operation,
        'status' => 'error',
        'error_message' => $e->getMessage(),
        'response_time' => 0,
        'created_at' => now()
    ]);
    
    // Check if we have cached data
    $cacheKey = $this->getCacheKey($operation, ['fallback' => true]);
    $cachedData = Cache::get($cacheKey);
    
    if ($cachedData) {
        Log::warning("Using cached data for failed Retell operation", [
            'operation' => $operation,
            'error' => $e->getMessage()
        ]);
        return $cachedData;
    }
    
    // Return mock data for development
    if (app()->environment('local', 'development')) {
        return $this->getMockData($operation);
    }
    
    // Production: return error
    return [
        'error' => 'Retell API temporarily unavailable',
        'message' => 'Please try again later or contact support',
        'operation' => $operation,
        'timestamp' => now()->toIso8601String()
    ];
}

private function getMockData(string $operation): array
{
    switch ($operation) {
        case 'listAgents':
            return [
                'agents' => [
                    [
                        'agent_id' => 'mock_agent_001',
                        'agent_name' => 'Mock Agent (API Offline)',
                        'voice_id' => '11labs-Adrian',
                        'language' => 'de-DE',
                        'webhook_url' => config('app.url') . '/api/webhooks/retell'
                    ]
                ]
            ];
        case 'listPhoneNumbers':
            return [
                'phone_numbers' => [
                    [
                        'phone_number' => '+49 30 12345678',
                        'phone_number_pretty' => '+49 (30) 123-45678',
                        'phone_number_type' => 'retell-twilio',
                        'area_code' => '030'
                    ]
                ]
            ];
        default:
            return [];
    }
}
```

### Solution 4: Create API Key Validation Command

```php
// app/Console/Commands/ValidateRetellApiKey.php

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Company;

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
            return 1;
        }
        
        // Check key format
        $this->info("\nAPI Key Analysis:");
        $this->line("  Format: " . (str_starts_with($apiKey, 'key_') ? '✓ Valid' : '✗ Invalid'));
        $this->line("  Length: " . strlen($apiKey) . " characters");
        $this->line("  Type: " . $this->detectKeyType($apiKey));
        
        // Test API endpoints
        $this->info("\nTesting API Endpoints:");
        
        $endpoints = [
            'Basic connectivity' => ['GET', 'https://api.retellai.com/'],
            'List agents' => ['GET', 'https://api.retellai.com/list-agents'],
            'List phone numbers' => ['GET', 'https://api.retellai.com/list-phone-numbers'],
        ];
        
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
                    
                    if ($name === 'List agents') {
                        $data = $response->json();
                        $count = is_array($data) ? count($data) : 0;
                        $this->line("  Found $count agents");
                    }
                } else {
                    $this->error("  ✗ Failed");
                    $this->line("  Response: " . substr($response->body(), 0, 200));
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Exception: " . $e->getMessage());
            }
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
        }
        
        return 0;
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
```

## Recommended Action Plan

### 1. **Immediate Actions**
```bash
# Run the validation command
php artisan retell:validate-api-key

# Check with debug logging
php test-retell-debug.php

# Monitor MCP metrics
php artisan mcp:monitor --service=retell
```

### 2. **Check Retell Dashboard**
1. Log into https://dashboard.retellai.com
2. Navigate to API Keys section
3. Check if current key is active
4. Generate new key if needed
5. Check billing/account status

### 3. **Update Configuration**
If new key is generated:
```php
// Update in database
$company = Company::find(1);
$company->retell_api_key = encrypt('new_key_here');
$company->save();

// Or update in .env
DEFAULT_RETELL_API_KEY=new_key_here
RETELL_TOKEN=new_key_here
```

### 4. **Contact Support**
If issue persists:
- Email: support@retellai.com
- Include:
  - API key (first/last 4 chars)
  - Error timestamps
  - Request examples

## MCP Integration Enhancements

The MCP system should:
1. **Monitor API Health** - Track all API calls and errors
2. **Implement Fallbacks** - Use cached data when API fails
3. **Auto-retry with Backoff** - Already implemented in SDK
4. **Alert on Failures** - Notify admins of persistent issues
5. **Mock Mode** - Allow development without API access

## Conclusion

The 500 error is likely due to:
1. Invalid/expired API key (most likely)
2. Account issues (billing, suspension)
3. Workspace mismatch

The solution involves validating the API key, potentially generating a new one, and implementing robust error handling through MCP to ensure system resilience.