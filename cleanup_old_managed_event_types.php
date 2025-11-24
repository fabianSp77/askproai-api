<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Services\CalcomV2Client;

echo "🧹 Cleanup Old MANAGED Event Types (3982xxx Range)\n";
echo str_repeat('=', 80) . "\n\n";

$company = Company::find(1);
$calcom = new CalcomV2Client($company);

// List of old MANAGED event type IDs to delete
$oldEventTypeIds = [
    // Service 440
    3982562, 3982564, 3982566, 3982568,
    // Service 442
    3982570, 3982572, 3982574, 3982576,
    // Service 444
    3982578, 3982580, 3982582, 3982584,
];

echo "Found " . count($oldEventTypeIds) . " old MANAGED event types to delete.\n\n";

$deleted = 0;
$failed = 0;

foreach ($oldEventTypeIds as $eventTypeId) {
    echo "Deleting Event Type ID {$eventTypeId}...";

    try {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => "Bearer {$company->calcom_v2_api_key}",
            'cal-api-version' => '2024-08-13',
            'Content-Type' => 'application/json'
        ])->delete("https://api.cal.com/v2/teams/{$company->calcom_team_id}/event-types/{$eventTypeId}");

        if ($response->successful()) {
            echo " ✅ Deleted\n";
            $deleted++;
        } else {
            $status = $response->status();
            $errorMsg = $response->json('error.message') ?? $response->json('message') ?? 'Unknown error';
            echo " ⚠️  Failed (HTTP {$status}): {$errorMsg}\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo " ❌ Exception: {$e->getMessage()}\n";
        $failed++;
    }

    // Rate limiting
    sleep(1);
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "📊 Summary:\n";
echo "  ✅ Deleted: {$deleted}\n";
echo "  ⚠️  Failed: {$failed}\n";
echo "\n🎉 Cleanup complete!\n";
