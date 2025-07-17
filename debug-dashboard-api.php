<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use App\Models\PortalUser;
use Illuminate\Support\Facades\DB;

echo "\n=== DASHBOARD API DEBUG ===\n";
echo "Time: " . now()->format('Y-m-d H:i:s') . "\n\n";

// 1. Test with each company
echo "1. TESTING DASHBOARD DATA FOR EACH COMPANY:\n";
echo "==========================================\n\n";

$companies = Company::withoutGlobalScopes()->get();

foreach ($companies as $company) {
    echo "Company: {$company->name} (ID: {$company->id})\n";
    echo "----------------------------------------\n";
    
    // Set company context
    app()->instance('current_company_id', $company->id);
    
    // Get call statistics
    $totalCalls = Call::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->count();
        
    $callsToday = Call::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->whereDate('created_at', today())
        ->count();
        
    $callsWithDuration = Call::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->whereNotNull('duration_sec')
        ->where('duration_sec', '>', 0);
        
    $avgDuration = $callsWithDuration->exists() 
        ? $callsWithDuration->avg('duration_sec') 
        : 0;
        
    echo "  Total calls: {$totalCalls}\n";
    echo "  Calls today: {$callsToday}\n";
    echo "  Average duration: " . ($avgDuration > 0 ? gmdate("i:s", $avgDuration) : "0:00") . " ({$avgDuration} seconds)\n";
    
    // Check for specific durations
    $callsWith106Sec = Call::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->where('duration_sec', 106)
        ->count();
        
    if ($callsWith106Sec > 0) {
        echo "  ⚠️  Found {$callsWith106Sec} calls with exactly 106 seconds (1:46) duration\n";
    }
    
    // Sample recent calls
    $recentCalls = Call::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->orderBy('created_at', 'desc')
        ->limit(3)
        ->get(['id', 'duration_sec', 'created_at', 'from_number']);
        
    if ($recentCalls->count() > 0) {
        echo "  Recent calls:\n";
        foreach ($recentCalls as $call) {
            echo "    - Call #{$call->id}: " . gmdate("i:s", $call->duration_sec ?? 0) . " duration, created " . $call->created_at->diffForHumans() . "\n";
        }
    }
    
    echo "\n";
}

// 2. Check for hardcoded values
echo "2. CHECKING FOR HARDCODED VALUES:\n";
echo "=================================\n\n";

// Check if 106 seconds appears as a default anywhere
$suspiciousCalls = Call::withoutGlobalScopes()
    ->where('duration_sec', 106)
    ->count();
    
echo "Total calls with exactly 106 seconds (1:46): {$suspiciousCalls}\n";

if ($suspiciousCalls > 0) {
    $sample = Call::withoutGlobalScopes()
        ->where('duration_sec', 106)
        ->limit(5)
        ->get(['id', 'company_id', 'created_at']);
        
    echo "Sample of these calls:\n";
    foreach ($sample as $call) {
        $company = Company::withoutGlobalScopes()->find($call->company_id);
        echo "  - Call #{$call->id} from company '{$company->name}' created at {$call->created_at}\n";
    }
}

// 3. Simulate dashboard API call
echo "\n3. SIMULATING DASHBOARD API CALL:\n";
echo "==================================\n\n";

// Test with company ID 1
$testCompanyId = 1;
$testCompany = Company::withoutGlobalScopes()->find($testCompanyId);

if ($testCompany) {
    echo "Testing with company: {$testCompany->name} (ID: {$testCompanyId})\n\n";
    
    // Simulate the dashboard controller logic
    $controller = new \App\Http\Controllers\Portal\Api\DashboardApiController();
    
    // Set company context
    app()->instance('current_company_id', $testCompanyId);
    
    // Create mock request
    $request = new \Illuminate\Http\Request();
    $request->merge(['range' => 'today']);
    
    // Get dashboard data
    try {
        $response = $controller->index($request);
        $data = json_decode($response->getContent(), true);
        
        echo "Dashboard Response:\n";
        echo "  Stats:\n";
        echo "    - Calls today: " . ($data['stats']['calls_today'] ?? 0) . "\n";
        echo "    - Appointments today: " . ($data['stats']['appointments_today'] ?? 0) . "\n";
        echo "    - New customers: " . ($data['stats']['new_customers'] ?? 0) . "\n";
        echo "    - Revenue today: €" . number_format($data['stats']['revenue_today'] ?? 0, 2) . "\n";
        echo "  Performance:\n";
        echo "    - Answer rate: " . ($data['performance']['answer_rate'] ?? 0) . "%\n";
        echo "    - Booking rate: " . ($data['performance']['booking_rate'] ?? 0) . "%\n";
        echo "    - Avg call duration: " . ($data['performance']['avg_call_duration'] ?? 0) . " seconds\n";
        echo "    - Formatted duration: " . gmdate("i:s", $data['performance']['avg_call_duration'] ?? 0) . "\n";
        
    } catch (\Exception $e) {
        echo "Error calling dashboard API: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}

// 4. Check for default values in database
echo "\n4. DATABASE STATISTICS:\n";
echo "======================\n\n";

$stats = DB::select("
    SELECT 
        company_id,
        COUNT(*) as total_calls,
        AVG(duration_sec) as avg_duration,
        MIN(duration_sec) as min_duration,
        MAX(duration_sec) as max_duration,
        SUM(CASE WHEN duration_sec = 106 THEN 1 ELSE 0 END) as calls_with_106_sec
    FROM calls
    WHERE duration_sec IS NOT NULL
    GROUP BY company_id
");

foreach ($stats as $stat) {
    $company = Company::withoutGlobalScopes()->find($stat->company_id);
    echo "Company: " . ($company ? $company->name : 'Unknown') . " (ID: {$stat->company_id})\n";
    echo "  - Total calls: {$stat->total_calls}\n";
    echo "  - Avg duration: " . gmdate("i:s", $stat->avg_duration) . " ({$stat->avg_duration} seconds)\n";
    echo "  - Min duration: " . gmdate("i:s", $stat->min_duration) . "\n";
    echo "  - Max duration: " . gmdate("i:s", $stat->max_duration) . "\n";
    if ($stat->calls_with_106_sec > 0) {
        echo "  - ⚠️  Calls with 1:46 duration: {$stat->calls_with_106_sec}\n";
    }
    echo "\n";
}

echo "\n=== END DEBUG ===\n";