<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;

echo "🔧 UPDATE CAL.COM EVENT TYPE ID\n";
echo str_repeat("=", 60) . "\n\n";

$company = Company::withoutGlobalScopes()->first();

if (!$company) {
    echo "❌ No company found!\n";
    exit(1);
}

// Use the 30 minute appointment type specified by user
$newEventTypeId = 2563193; // "30 minuten" - vom Benutzer angegeben

echo "🏢 Company: " . $company->name . "\n";
echo "Old Event Type ID: " . ($company->calcom_event_type_id ?? 'None') . "\n";
echo "New Event Type ID: $newEventTypeId (30 Minuten Termin)\n\n";

$company->calcom_event_type_id = $newEventTypeId;
$company->save();

echo "✅ Event Type ID updated successfully!\n\n";

echo "📊 CURRENT CONFIGURATION:\n";
echo str_repeat("-", 40) . "\n";
echo "Cal.com API Key: " . ($company->calcom_api_key ? '✅ Set' : '❌ Missing') . "\n";
echo "Cal.com Event Type ID: " . $company->calcom_event_type_id . "\n";
echo "Cal.com Team Slug: " . ($company->calcom_team_slug ?? 'Not set') . "\n";

echo "\n✅ Configuration updated! Appointments will now be booked as 30-minute slots.\n";