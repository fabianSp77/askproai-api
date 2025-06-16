<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Branch;
use App\Services\CalcomService;

$branch = Branch::where('phone_number', '+493083793369')->first();
echo "Testing Cal.com availability for Branch: " . $branch->name . "\n";
echo "Event Type ID: " . $branch->calcom_event_type_id . "\n";
echo "API Key: " . substr($branch->calcom_api_key, 0, 20) . "...\n\n";

// Teste verschiedene Zeiträume
$tests = [
    ['from' => 'now', 'to' => '+2 hours'],
    ['from' => 'tomorrow 09:00', 'to' => 'tomorrow 18:00'],
    ['from' => '+2 days 09:00', 'to' => '+2 days 18:00'],
    ['from' => '+7 days 09:00', 'to' => '+7 days 18:00']
];

foreach ($tests as $test) {
    $dateFrom = date('Y-m-d\TH:i:s\Z', strtotime($test['from']));
    $dateTo = date('Y-m-d\TH:i:s\Z', strtotime($test['to']));
    
    echo "Testing from $dateFrom to $dateTo\n";
    
    $url = "https://api.cal.com/v1/availability?apiKey={$branch->calcom_api_key}&eventTypeId={$branch->calcom_event_type_id}&dateFrom={$dateFrom}&dateTo={$dateTo}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response: " . substr($response, 0, 200) . "...\n\n";
}
