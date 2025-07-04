<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;

echo "🔧 UPDATE BRANCH CAL.COM EVENT TYPE\n";
echo str_repeat("=", 60) . "\n\n";

$branch = Branch::withoutGlobalScopes()->first();

if (!$branch) {
    echo "❌ No branch found!\n";
    exit(1);
}

$oldEventTypeId = $branch->calcom_event_type_id;
$newEventTypeId = 2563193;

echo "📍 Branch: " . $branch->name . "\n";
echo "Old Event Type ID: " . ($oldEventTypeId ?? 'None') . "\n";
echo "New Event Type ID: $newEventTypeId\n\n";

// Update branch event type ID
$branch->calcom_event_type_id = $newEventTypeId;
$branch->save();

echo "✅ Branch Event Type ID updated!\n\n";

// Show current configuration
echo "📊 CURRENT CONFIGURATION:\n";
echo str_repeat("-", 40) . "\n";
$effectiveConfig = $branch->getEffectiveCalcomConfig();
echo "Effective Event Type ID: " . ($effectiveConfig['event_type_id'] ?? 'None') . "\n";
echo "Calendar Mode: " . $branch->calendar_mode . "\n";

echo "\n✅ Configuration complete!\n";