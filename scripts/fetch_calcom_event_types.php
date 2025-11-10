<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Http;

echo "\n═══════════════════════════════════════════════════════════\n";
echo "  FETCHING CAL.COM EVENT TYPES\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$company = Company::find(1);

echo "Company: {$company->name}\n";
echo "Cal.com Team ID: {$company->calcom_team_id}\n";
echo "API Key: " . substr($company->calcom_api_key, 0, 20) . "...\n\n";

// Try direct API call
$apiKey = $company->calcom_api_key;
$teamId = $company->calcom_team_id;

echo "Trying V1 API directly...\n";
$v1Url = "https://api.cal.com/v1/event-types?apiKey={$apiKey}&teamId={$teamId}";

try {
    $response = Http::get($v1Url);

    echo "Status: " . $response->status() . "\n";

    if ($response->successful()) {
        $data = $response->json();

        if (isset($data['event_types'])) {
            $eventTypes = $data['event_types'];
            echo "✅ Found " . count($eventTypes) . " event types\n\n";

            foreach ($eventTypes as $et) {
                echo "─────────────────────────────────────────────────────────\n";
                echo "Title: {$et['title']}\n";
                echo "ID: {$et['id']}\n";
                echo "Slug: {$et['slug']}\n";
                echo "Length: {$et['length']} min\n";
                echo "Team ID: " . ($et['teamId'] ?? 'none') . "\n";
                echo "\n";
            }
        } else {
            echo "⚠️  No 'event_types' key in response\n";
            echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ API call failed\n";
        echo "Body: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
