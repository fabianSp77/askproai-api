<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

// Set company context
app()->instance('current_company_id', 1);

// Test the dashboard API controller
$controller = new \App\Http\Controllers\Portal\Api\DashboardApiController();

// Create a request
$request = new \Illuminate\Http\Request();
$request->merge(['range' => 'today']);

// Get the response
$response = $controller->index($request);
$data = json_decode($response->getContent(), true);

echo "=== Dashboard API Response for 'today' ===\n";
if (!$data) {
    echo "Error: No data returned\n";
    echo "Response: " . $response->getContent() . "\n";
    exit;
}
if (!isset($data['stats'])) {
    echo "Error: 'stats' key not found in response\n";
    echo "Available keys: " . implode(', ', array_keys($data)) . "\n";
    echo "Full response:\n";
    print_r($data);
    exit;
}
echo "Calls Today: " . $data['stats']['calls_today'] . "\n";
echo "Average Call Duration: " . $data['performance']['avg_call_duration'] . " seconds\n";
echo "Expected duration display: ";
if ($data['stats']['calls_today'] === 0) {
    echo "-\n";
} else {
    $duration = $data['performance']['avg_call_duration'];
    echo sprintf("%d:%02d\n", floor($duration / 60), $duration % 60);
}

echo "\n=== Testing other ranges ===\n";
foreach (['week', 'month', 'year'] as $range) {
    $request = new \Illuminate\Http\Request();
    $request->merge(['range' => $range]);
    $response = $controller->index($request);
    $data = json_decode($response->getContent(), true);
    
    echo "\nRange: $range\n";
    echo "Calls: " . $data['stats']['calls_today'] . "\n";
    echo "Avg Duration: " . $data['performance']['avg_call_duration'] . " seconds\n";
}