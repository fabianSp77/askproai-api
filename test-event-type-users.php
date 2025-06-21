<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;

$company = Company::first();
if (!$company) {
    echo "No company found\n";
    exit(1);
}

$apiKey = decrypt($company->calcom_api_key);

// Get a specific event type details
$eventTypeId = 2026301; // "15 Minuten Termin"

echo "Getting details for Event Type ID: $eventTypeId\n\n";

// Try v2 API with specific event type
$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json',
])->get("https://api.cal.com/v2/event-types/$eventTypeId");

if ($response->successful()) {
    $data = $response->json();
    echo "Event Type Details:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Failed to get event type details: " . $response->status() . "\n";
}

// Also try getting users separately
echo "\n\nTrying to get users/teams:\n";

// Try getting team members
$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json',
])->get("https://api.cal.com/v2/organizations/teams");

if ($response->successful()) {
    $teams = $response->json();
    echo "Teams:\n";
    echo json_encode($teams, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Failed to get teams: " . $response->status() . "\n";
}