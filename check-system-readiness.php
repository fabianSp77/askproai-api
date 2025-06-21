<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Call;
use App\Models\Appointment;
use App\Services\RetellV2Service;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

echo "\n";
echo "====================================\n";
echo "  AskProAI System Readiness Check   \n";
echo "====================================\n\n";

$checksPassed = 0;
$totalChecks = 0;

// 1. Database Connection
echo "1. Database Connection: ";
$totalChecks++;
try {
    DB::connection()->getPdo();
    echo "✅ Connected\n";
    $checksPassed++;
} catch (\Exception $e) {
    echo "❌ Failed - " . $e->getMessage() . "\n";
}

// 2. Redis Connection
echo "2. Redis Connection: ";
$totalChecks++;
try {
    Redis::ping();
    echo "✅ Connected\n";
    $checksPassed++;
} catch (\Exception $e) {
    echo "❌ Failed - " . $e->getMessage() . "\n";
}

// 3. Failed Jobs Count
echo "3. Failed Jobs: ";
$totalChecks++;
try {
    $failedJobs = DB::table('failed_jobs')->count();
    if ($failedJobs > 0) {
        echo "⚠️  {$failedJobs} failed jobs need attention\n";
    } else {
        echo "✅ No failed jobs\n";
        $checksPassed++;
    }
} catch (\Exception $e) {
    echo "❌ Error checking - " . $e->getMessage() . "\n";
}

// 4. Horizon Status
echo "4. Horizon Status: ";
$totalChecks++;
try {
    $masters = app('Laravel\Horizon\Contracts\MasterSupervisorRepository')->all();
    if (!empty($masters) && $masters[0]->status === 'running') {
        echo "✅ Running\n";
        $checksPassed++;
    } else {
        echo "❌ Not running - Start with: php artisan horizon\n";
    }
} catch (\Exception $e) {
    echo "❌ Error - " . $e->getMessage() . "\n";
}

// 5. Company Configuration
echo "5. Company Configuration: ";
$totalChecks++;
try {
    $company = Company::first();
    if ($company) {
        echo "✅ Found: {$company->name}\n";
        $checksPassed++;
        
        // Check API keys
        echo "   - Retell API Key: " . ($company->retell_api_key ? "✅ Set" : "❌ Missing") . "\n";
        echo "   - Cal.com API Key: " . ($company->calcom_api_key ? "✅ Set" : "❌ Missing") . "\n";
    } else {
        echo "❌ No company found\n";
    }
} catch (\Exception $e) {
    echo "❌ Error - " . $e->getMessage() . "\n";
}

// 6. Retell Integration
echo "6. Retell Integration: ";
$totalChecks++;
try {
    $company = Company::first();
    if ($company && $company->retell_api_key) {
        // Set company context
        app()->instance('current_company_id', $company->id);
        
        $retell = new RetellV2Service();
        $calls = $retell->listCalls(1); // Try to get 1 call
        if ($calls !== null) {
            echo "✅ Connected\n";
            $checksPassed++;
        } else {
            echo "❌ Connection failed\n";
        }
    } else {
        echo "❌ No API key configured\n";
    }
} catch (\Exception $e) {
    echo "❌ Error - " . substr($e->getMessage(), 0, 50) . "...\n";
}

// 7. Cal.com Integration
echo "7. Cal.com Integration: ";
$totalChecks++;
try {
    $company = Company::first();
    if ($company && $company->calcom_api_key) {
        // Set company context
        app()->instance('current_company_id', $company->id);
        
        $calcom = new CalcomV2Service();
        $result = $calcom->getMe();
        if ($result && isset($result['user'])) {
            echo "✅ Connected as " . ($result['user']['username'] ?? 'user') . "\n";
            $checksPassed++;
        } else {
            echo "❌ Connection failed\n";
        }
    } else {
        echo "❌ No API key configured\n";
    }
} catch (\Exception $e) {
    echo "❌ Error - " . substr($e->getMessage(), 0, 50) . "...\n";
}

// 8. Recent Activity
echo "8. Recent Activity:\n";
try {
    $recentCalls = Call::where('created_at', '>=', now()->subDays(1))->count();
    $recentAppointments = Appointment::where('created_at', '>=', now()->subDays(1))->count();
    
    echo "   - Calls (24h): {$recentCalls}\n";
    echo "   - Appointments (24h): {$recentAppointments}\n";
} catch (\Exception $e) {
    echo "   ❌ Error - " . $e->getMessage() . "\n";
}

// 9. MCP Endpoints
echo "9. MCP Endpoints: ";
$totalChecks++;
try {
    // Test if routes are registered
    $routes = app('router')->getRoutes();
    $mcpRoutes = 0;
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'api/mcp/')) {
            $mcpRoutes++;
        }
    }
    if ($mcpRoutes > 0) {
        echo "✅ {$mcpRoutes} MCP routes registered\n";
        $checksPassed++;
    } else {
        echo "❌ No MCP routes found\n";
    }
} catch (\Exception $e) {
    echo "❌ Error - " . $e->getMessage() . "\n";
}

// 10. System Improvements Page
echo "10. System Improvements Page: ";
$totalChecks++;
try {
    if (class_exists('\App\Filament\Admin\Pages\SystemImprovements')) {
        echo "✅ Available at /admin/system-improvements\n";
        $checksPassed++;
    } else {
        echo "❌ Page not found\n";
    }
} catch (\Exception $e) {
    echo "❌ Error - " . $e->getMessage() . "\n";
}

// Summary
echo "\n====================================\n";
echo "  Summary: {$checksPassed}/{$totalChecks} checks passed\n";
echo "====================================\n\n";

if ($checksPassed < $totalChecks) {
    echo "⚠️  System needs attention!\n\n";
    echo "Required actions:\n";
    
    $company = Company::first();
    if (!$company || !$company->retell_api_key || !$company->calcom_api_key) {
        echo "1. Configure API keys in .env:\n";
        echo "   DEFAULT_RETELL_API_KEY=your_key\n";
        echo "   DEFAULT_CALCOM_API_KEY=your_key\n\n";
    }
    
    if ($failedJobs > 0) {
        echo "2. Process failed jobs:\n";
        echo "   php artisan queue:retry all\n\n";
    }
    
    echo "3. Ensure Horizon is running:\n";
    echo "   php artisan horizon\n\n";
} else {
    echo "✅ System is ready!\n\n";
    echo "You can now:\n";
    echo "- Access System Improvements at: /admin/system-improvements\n";
    echo "- Use MCP endpoints for system analysis\n";
    echo "- Monitor real-time performance\n";
}

echo "\n";