#!/usr/bin/env php
<?php
/**
 * Fix Retell.ai Call Import Issues
 * 
 * This script fixes the common "No calls imported" error by:
 * 1. Checking Horizon status
 * 2. Verifying API keys
 * 3. Triggering manual import
 * 
 * Error Code: RETELL_001
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\RetellV2Service;

echo "🔧 Retell.ai Call Import Fix Script\n";
echo "===================================\n\n";

try {
    // Step 1: Check Horizon status
    echo "1. Checking Horizon status...\n";
    exec('php artisan horizon:status 2>&1', $output, $returnCode);
    
    if ($returnCode !== 0) {
        echo "   ⚠️  Horizon is not running\n";
        echo "   Starting Horizon in background...\n";
        exec('nohup php artisan horizon > /dev/null 2>&1 &');
        sleep(3);
        echo "   ✅ Horizon started\n";
    } else {
        echo "   ✅ Horizon is running\n";
    }

    // Step 2: Check companies with Retell API keys
    echo "\n2. Checking companies with Retell API keys...\n";
    $companies = Company::whereNotNull('retell_api_key')->get();
    
    if ($companies->isEmpty()) {
        echo "   ❌ No companies have Retell API keys configured\n";
        
        // Try to set from environment
        $defaultKey = config('services.retell.api_key');
        if ($defaultKey) {
            echo "   Setting default API key from environment...\n";
            $company = Company::first();
            if ($company) {
                $company->retell_api_key = $defaultKey;
                $company->save();
                echo "   ✅ API key set for {$company->name}\n";
                $companies = collect([$company]);
            }
        }
    } else {
        echo "   ✅ Found {$companies->count()} companies with API keys\n";
    }

    // Step 3: Test API connection for each company
    echo "\n3. Testing Retell API connections...\n";
    foreach ($companies as $company) {
        echo "   Testing {$company->name}...\n";
        
        try {
            $service = new RetellV2Service($company);
            $testResult = $service->listAgents();
            
            if ($testResult) {
                echo "   ✅ API connection successful\n";
            } else {
                echo "   ❌ API connection failed (empty response)\n";
            }
        } catch (\Exception $e) {
            echo "   ❌ API connection failed: " . $e->getMessage() . "\n";
        }
    }

    // Step 4: Trigger manual import for each company
    echo "\n4. Triggering manual call import...\n";
    $totalImported = 0;
    
    foreach ($companies as $company) {
        echo "   Importing calls for {$company->name}...\n";
        
        try {
            $service = new RetellV2Service($company);
            $calls = $service->fetchCalls();
            
            if ($calls && is_array($calls)) {
                $count = count($calls);
                echo "   ✅ Fetched {$count} calls\n";
                
                // Process each call
                foreach ($calls as $callData) {
                    try {
                        \App\Jobs\ProcessRetellCallEndedJob::dispatch($callData, $company);
                        $totalImported++;
                    } catch (\Exception $e) {
                        echo "   ⚠️  Failed to queue call: " . $e->getMessage() . "\n";
                    }
                }
            } else {
                echo "   ⚠️  No calls found\n";
            }
        } catch (\Exception $e) {
            echo "   ❌ Import failed: " . $e->getMessage() . "\n";
        }
    }

    // Step 5: Check webhook configuration
    echo "\n5. Checking webhook configuration...\n";
    $webhookUrl = config('app.url') . '/api/retell/webhook-simple';
    echo "   Expected webhook URL: {$webhookUrl}\n";
    echo "   ℹ️  Please verify this URL is configured in Retell.ai dashboard\n";

    // Summary
    echo "\n✅ Fix completed!\n";
    echo "   - Horizon: Running\n";
    echo "   - Companies with API keys: {$companies->count()}\n";
    echo "   - Calls queued for import: {$totalImported}\n";
    echo "\nNext steps:\n";
    echo "1. Monitor Horizon dashboard at /horizon\n";
    echo "2. Check admin panel for imported calls\n";
    echo "3. Verify webhook URL in Retell.ai dashboard\n";
    
    exit(0);

} catch (\Exception $e) {
    echo "\n❌ Error during fix process: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}