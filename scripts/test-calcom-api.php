<?php

use App\Services\CalcomService;
use Illuminate\Support\Facades\Http;

echo "=== Testing Cal.com API Configuration ===\n";

// Check configuration
$apiKey = config('services.calcom.api_key');
$baseUrl = config('services.calcom.base_url');

echo "API Key present: " . ($apiKey ? 'Yes (' . substr($apiKey, 0, 15) . '...)' : 'No') . "\n";
echo "Base URL: " . ($baseUrl ?: 'Not configured') . "\n";

if (!$apiKey) {
    echo "❌ ERROR: Cal.com API key not configured!\n";
    exit(1);
}

echo "\n=== Testing Cal.com API Connection ===\n";

try {
    // Test API connection
    $response = Http::withHeaders([
        'Accept' => 'application/json',
    ])->get($baseUrl . '/event-types?apiKey=' . $apiKey);

    echo "API Response Status: " . $response->status() . "\n";

    if ($response->successful()) {
        echo "✅ SUCCESS: Cal.com API connection working!\n";

        $data = $response->json();
        if (isset($data['event_types'])) {
            echo "Found " . count($data['event_types']) . " event types in Cal.com\n";

            // Show first few event types
            foreach (array_slice($data['event_types'], 0, 3) as $eventType) {
                echo "  - ID: " . $eventType['id'] . " | Title: " . $eventType['title'] . "\n";
            }
        }
    } else {
        echo "❌ ERROR: " . $response->status() . " - " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}