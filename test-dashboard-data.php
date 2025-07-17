<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

// Set company context
app()->instance('current_company_id', 1);

// Login as portal user
$portalUser = \App\Models\PortalUser::where('company_id', 1)->first();
if (!$portalUser) {
    echo "No portal user found for company 1\n";
    exit;
}

\Auth::guard('portal')->login($portalUser);

// Now test dashboard data directly using the private methods
$controller = new \App\Http\Controllers\Portal\Api\DashboardApiController();

// Use reflection to access private methods
$reflection = new ReflectionClass($controller);

// Test date range calculation
$getDateRange = $reflection->getMethod('getDateRange');
$getDateRange->setAccessible(true);
list($startDate, $endDate) = $getDateRange->invoke($controller, 'today');

echo "=== Testing 'today' range ===\n";
echo "Start: " . $startDate . "\n";
echo "End: " . $endDate . "\n\n";

// Test performance metrics
$getPerformanceMetrics = $reflection->getMethod('getPerformanceMetrics');
$getPerformanceMetrics->setAccessible(true);
$performance = $getPerformanceMetrics->invoke($controller, 1, $startDate, $endDate);

echo "=== Performance Metrics ===\n";
echo "Average Call Duration: " . $performance['avg_call_duration'] . " seconds\n";
echo "Answer Rate: " . $performance['answer_rate'] . "%\n";
echo "Booking Rate: " . $performance['booking_rate'] . "%\n\n";

// Test main stats
$getMainStats = $reflection->getMethod('getMainStats');
$getMainStats->setAccessible(true);
$stats = $getMainStats->invoke($controller, 1, $startDate, $endDate);

echo "=== Main Stats ===\n";
echo "Calls Today: " . $stats['calls_today'] . "\n";
echo "Appointments Today: " . $stats['appointments_today'] . "\n";
echo "New Customers: " . $stats['new_customers'] . "\n";
echo "Revenue Today: €" . number_format($stats['revenue_today'], 2) . "\n\n";

// Check what the frontend should display
echo "=== Frontend Display Logic ===\n";
echo "Should display for average duration: ";
if ($stats['calls_today'] === 0) {
    echo "-\n";
} else {
    $duration = $performance['avg_call_duration'];
    echo sprintf("%d:%02d\n", floor($duration / 60), $duration % 60);
}

// Check actual calls in database
echo "\n=== Direct Database Check ===\n";
$calls = \App\Models\Call::where('company_id', 1)
    ->whereDate('created_at', now()->toDateString())
    ->get();
echo "Calls found for today: " . $calls->count() . "\n";

if ($calls->count() > 0) {
    echo "Call IDs: " . $calls->pluck('id')->implode(', ') . "\n";
}