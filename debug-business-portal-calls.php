<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use App\Models\PortalUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

echo "\n=== BUSINESS PORTAL CALLS DEBUG ===\n";
echo "Time: " . now()->format('Y-m-d H:i:s') . "\n\n";

// 1. Check session data
echo "1. SESSION DATA CHECK:\n";
echo "-------------------\n";

// Start session if not started
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "Session is active\n";
    echo "Session ID: " . session_id() . "\n";
    
    // Check for portal user session
    if (isset($_SESSION['portal_user_id'])) {
        echo "Portal User ID in session: " . $_SESSION['portal_user_id'] . "\n";
        $portalUser = PortalUser::find($_SESSION['portal_user_id']);
        if ($portalUser) {
            echo "Portal User Email: " . $portalUser->email . "\n";
            echo "Portal User Company ID: " . $portalUser->company_id . "\n";
        }
    } else {
        echo "No portal_user_id in session\n";
    }
    
    // Check for admin impersonation
    if (isset($_SESSION['admin_impersonating_portal'])) {
        echo "Admin impersonation active: " . $_SESSION['admin_impersonating_portal'] . "\n";
    }
    
    if (isset($_SESSION['admin_id'])) {
        echo "Admin ID in session: " . $_SESSION['admin_id'] . "\n";
    }
} else {
    echo "No active session\n";
}

// 2. Check all companies and their call counts
echo "\n2. COMPANIES AND CALL COUNTS:\n";
echo "----------------------------\n";

$companies = Company::withCount('calls')->get();
foreach ($companies as $company) {
    echo "Company: {$company->name} (ID: {$company->id})\n";
    echo "  - Total calls: {$company->calls_count}\n";
    echo "  - Active: " . ($company->is_active ? 'Yes' : 'No') . "\n";
    
    // Get call stats for this company
    $stats = Call::where('company_id', $company->id)
        ->selectRaw('
            COUNT(*) as total_calls,
            AVG(duration_sec) as avg_duration,
            MIN(created_at) as first_call,
            MAX(created_at) as last_call
        ')
        ->first();
    
    if ($stats->total_calls > 0) {
        echo "  - Average duration: " . gmdate("i:s", $stats->avg_duration) . "\n";
        echo "  - First call: " . $stats->first_call . "\n";
        echo "  - Last call: " . $stats->last_call . "\n";
    }
    echo "\n";
}

// 3. Check recent calls regardless of company
echo "3. RECENT CALLS (LAST 10):\n";
echo "-------------------------\n";

$recentCalls = Call::with(['company', 'branch'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($recentCalls as $call) {
    echo "Call ID: {$call->id}\n";
    echo "  - Company: " . ($call->company ? $call->company->name : 'NULL') . " (ID: {$call->company_id})\n";
    echo "  - Branch: " . ($call->branch ? $call->branch->name : 'NULL') . " (ID: {$call->branch_id})\n";
    echo "  - Duration: " . gmdate("i:s", $call->duration_sec) . "\n";
    echo "  - Created: {$call->created_at}\n";
    echo "  - Status: {$call->status}\n";
    echo "\n";
}

// 4. Check if TenantScope might be affecting queries
echo "4. TENANT SCOPE CHECK:\n";
echo "--------------------\n";

// Check calls without any scope
$totalCallsNoScope = Call::withoutGlobalScopes()->count();
echo "Total calls (no scopes): {$totalCallsNoScope}\n";

// Check calls with tenant scope
$totalCallsWithScope = Call::count();
echo "Total calls (with scopes): {$totalCallsWithScope}\n";

// 5. Check specific portal user data
echo "\n5. PORTAL USER CHECK:\n";
echo "-------------------\n";

// Find portal users
$portalUsers = PortalUser::with('company')->limit(5)->get();
foreach ($portalUsers as $user) {
    echo "Portal User: {$user->email}\n";
    echo "  - Company: " . ($user->company ? $user->company->name : 'NULL') . " (ID: {$user->company_id})\n";
    echo "  - Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    
    // Check calls for this company
    $callCount = Call::where('company_id', $user->company_id)->count();
    echo "  - Calls for company: {$callCount}\n";
    echo "\n";
}

// 6. Check the actual query that might be used in the dashboard
echo "6. DASHBOARD QUERY SIMULATION:\n";
echo "-----------------------------\n";

// Simulate what the dashboard might be doing
$companyId = 1; // Replace with actual company ID from session
echo "Simulating query for company_id = {$companyId}\n";

$dashboardStats = Call::where('company_id', $companyId)
    ->selectRaw('
        COUNT(*) as total_calls,
        AVG(duration_sec) as avg_duration_sec,
        SUM(duration_sec) as total_duration_sec
    ')
    ->first();

echo "Results:\n";
echo "  - Total calls: {$dashboardStats->total_calls}\n";
echo "  - Average duration (seconds): " . ($dashboardStats->avg_duration_sec ?? 0) . "\n";
echo "  - Average duration (formatted): " . gmdate("i:s", $dashboardStats->avg_duration_sec ?? 0) . "\n";
echo "  - Total duration (seconds): " . ($dashboardStats->total_duration_sec ?? 0) . "\n";

// 7. Check if there's any hardcoded or default value
echo "\n7. CHECK FOR HARDCODED VALUES:\n";
echo "-----------------------------\n";

// Check if 1:46 (106 seconds) appears anywhere
$suspiciousDuration = 106; // 1 minute 46 seconds
$callsWithDuration = Call::where('duration_sec', $suspiciousDuration)->count();
echo "Calls with exactly 1:46 duration: {$callsWithDuration}\n";

// Check average of all calls
$overallAvg = Call::avg('duration_sec');
echo "Overall average duration (all companies): " . gmdate("i:s", $overallAvg ?? 0) . "\n";

// 8. Database query log
echo "\n8. SAMPLE QUERY LOG:\n";
echo "-------------------\n";

DB::enableQueryLog();

// Run a typical dashboard query
$testQuery = Call::where('company_id', 1)
    ->whereDate('created_at', '>=', now()->subDays(30))
    ->count();

$queries = DB::getQueryLog();
foreach ($queries as $query) {
    echo "Query: " . $query['query'] . "\n";
    echo "Bindings: " . json_encode($query['bindings']) . "\n";
    echo "Time: " . $query['time'] . "ms\n\n";
}

echo "\n=== END DEBUG ===\n";