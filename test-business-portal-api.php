<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;

// Start session before any output
session_start();

echo "\n=== BUSINESS PORTAL API TEST ===\n";
echo "Time: " . now()->format('Y-m-d H:i:s') . "\n\n";

// 1. Find a portal user to simulate
$portalUser = PortalUser::first();
if (!$portalUser) {
    echo "No portal users found. Creating test user...\n";
    $company = Company::first();
    if (!$company) {
        die("No companies found in database!\n");
    }
    
    $portalUser = PortalUser::create([
        'company_id' => $company->id,
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'name' => 'Test User',
        'is_active' => true
    ]);
}

echo "Using portal user: {$portalUser->email} (Company ID: {$portalUser->company_id})\n";

// 2. Simulate admin viewing session
$_SESSION['is_admin_viewing'] = true;
$_SESSION['admin_impersonation'] = [
    'company_id' => $portalUser->company_id,
    'portal_user_id' => $portalUser->id
];

// 3. Make API request to dashboard-test endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/business/api/dashboard-test?range=month');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'X-Requested-With: XMLHttpRequest'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nAPI Response (HTTP {$httpCode}):\n";
echo "===============================\n";

if ($response) {
    $data = json_decode($response, true);
    if ($data) {
        echo "Stats:\n";
        echo "  - Calls today: " . ($data['stats']['calls_today'] ?? 'N/A') . "\n";
        echo "  - Appointments today: " . ($data['stats']['appointments_today'] ?? 'N/A') . "\n";
        echo "  - New customers: " . ($data['stats']['new_customers'] ?? 'N/A') . "\n";
        echo "  - Revenue today: €" . number_format($data['stats']['revenue_today'] ?? 0, 2) . "\n";
        
        echo "\nPerformance:\n";
        echo "  - Answer rate: " . ($data['performance']['answer_rate'] ?? 'N/A') . "%\n";
        echo "  - Booking rate: " . ($data['performance']['booking_rate'] ?? 'N/A') . "%\n";
        echo "  - Avg call duration: " . ($data['performance']['avg_call_duration'] ?? 'N/A') . " seconds\n";
        echo "  - Formatted: " . gmdate("i:s", $data['performance']['avg_call_duration'] ?? 0) . "\n";
        
        echo "\nTrends:\n";
        foreach ($data['trends'] ?? [] as $key => $trend) {
            echo "  - {$key}: {$trend['value']} (change: {$trend['change']}%)\n";
        }
        
        echo "\nRecent Calls: " . count($data['recentCalls'] ?? []) . "\n";
        foreach (array_slice($data['recentCalls'] ?? [], 0, 3) as $call) {
            echo "  - Call from {$call['from_number']} - Duration: {$call['duration']}s\n";
        }
        
        echo "\nRaw JSON (performance section):\n";
        echo json_encode($data['performance'] ?? [], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Failed to decode JSON response\n";
        echo "Raw response: " . $response . "\n";
    }
} else {
    echo "No response received\n";
}

// 4. Direct database check for the company
echo "\n\nDirect Database Check:\n";
echo "=====================\n";

$company = Company::find($portalUser->company_id);
if ($company) {
    $callsToday = \App\Models\Call::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->whereDate('created_at', today())
        ->count();
        
    $callsMonth = \App\Models\Call::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->whereDate('created_at', '>=', now()->startOfMonth())
        ->count();
        
    $avgDurationMonth = \App\Models\Call::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->whereDate('created_at', '>=', now()->startOfMonth())
        ->whereNotNull('duration_sec')
        ->where('duration_sec', '>', 0)
        ->avg('duration_sec');
        
    echo "Company: {$company->name} (ID: {$company->id})\n";
    echo "Calls today: {$callsToday}\n";
    echo "Calls this month: {$callsMonth}\n";
    echo "Avg duration this month: " . gmdate("i:s", $avgDurationMonth ?? 0) . " ({$avgDurationMonth} seconds)\n";
}

echo "\n=== END TEST ===\n";