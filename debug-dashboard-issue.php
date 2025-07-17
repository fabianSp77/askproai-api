<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use App\Http\Controllers\Portal\Api\DashboardApiController;
use Illuminate\Http\Request;

echo "\n=== DASHBOARD ISSUE DEBUG ===\n";
echo "Time: " . now()->format('Y-m-d H:i:s') . "\n\n";

// Test company ID 1 (Krückeberg Servicegruppe)
$companyId = 1;
$company = Company::withoutGlobalScopes()->find($companyId);

echo "Testing with company: {$company->name} (ID: {$companyId})\n";
echo "================================================\n\n";

// 1. Check actual data in database
echo "1. DATABASE DATA:\n";
echo "-----------------\n";

$ranges = ['today', 'week', 'month', 'year'];

foreach ($ranges as $range) {
    list($startDate, $endDate) = getDateRange($range);
    
    $calls = Call::withoutGlobalScopes()
        ->where('company_id', $companyId)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->whereNotNull('duration_sec')
        ->where('duration_sec', '>', 0);
        
    $totalCalls = $calls->count();
    $avgDuration = $calls->exists() ? $calls->avg('duration_sec') : 0;
    
    echo "Range: {$range}\n";
    echo "  - Period: {$startDate->format('Y-m-d H:i')} to {$endDate->format('Y-m-d H:i')}\n";
    echo "  - Total calls: {$totalCalls}\n";
    echo "  - Avg duration: " . gmdate("i:s", $avgDuration) . " ({$avgDuration} seconds)\n\n";
}

// 2. Test the API controller directly
echo "2. API CONTROLLER RESPONSE:\n";
echo "---------------------------\n";

// Set company context
app()->instance('current_company_id', $companyId);

$controller = new DashboardApiController();

foreach ($ranges as $range) {
    $request = new Request(['range' => $range]);
    
    try {
        $response = $controller->index($request);
        $data = json_decode($response->getContent(), true);
        
        echo "Range: {$range}\n";
        echo "  - Calls stat: " . ($data['stats']['calls_today'] ?? 'N/A') . "\n";
        echo "  - Avg duration: " . ($data['performance']['avg_call_duration'] ?? 'N/A') . " seconds\n";
        echo "  - Formatted: " . gmdate("i:s", $data['performance']['avg_call_duration'] ?? 0) . "\n\n";
    } catch (\Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n\n";
    }
}

// 3. Check for specific issue - 106 seconds
echo "3. INVESTIGATING 1:46 (106 seconds) ISSUE:\n";
echo "------------------------------------------\n";

$callsWith106 = Call::withoutGlobalScopes()
    ->where('company_id', $companyId)
    ->where('duration_sec', 106)
    ->count();
    
echo "Calls with exactly 106 seconds: {$callsWith106}\n";

// Check distribution of call durations
$distribution = Call::withoutGlobalScopes()
    ->where('company_id', $companyId)
    ->whereNotNull('duration_sec')
    ->selectRaw('
        FLOOR(duration_sec / 30) * 30 as duration_bucket,
        COUNT(*) as count
    ')
    ->groupBy('duration_bucket')
    ->orderBy('duration_bucket')
    ->get();
    
echo "\nDuration distribution (30-second buckets):\n";
foreach ($distribution as $bucket) {
    $start = gmdate("i:s", $bucket->duration_bucket);
    $end = gmdate("i:s", $bucket->duration_bucket + 29);
    echo "  {$start} - {$end}: {$bucket->count} calls\n";
}

// 4. Check if there's a caching issue
echo "\n4. CACHE CHECK:\n";
echo "---------------\n";

$cacheKey = "portal.stats.{$companyId}.admin";
$cachedStats = \Illuminate\Support\Facades\Cache::get($cacheKey);

if ($cachedStats) {
    echo "Found cached stats:\n";
    echo json_encode($cachedStats, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "No cached stats found for this company.\n";
}

// Helper function
function getDateRange($range)
{
    $endDate = now();
    
    switch ($range) {
        case 'today':
            $startDate = now()->startOfDay();
            break;
        case 'week':
            $startDate = now()->startOfWeek();
            break;
        case 'month':
            $startDate = now()->startOfMonth();
            break;
        case 'year':
            $startDate = now()->startOfYear();
            break;
        default:
            $startDate = now()->startOfDay();
    }
    
    return [$startDate, $endDate];
}

echo "\n=== END DEBUG ===\n";