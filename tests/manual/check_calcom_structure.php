<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CAL.COM EVENT TYPES STRUKTUR ===\n\n";

// 1. Table structure
echo "TABELLEN-STRUKTUR:\n";
$columns = DB::select("SHOW COLUMNS FROM calcom_event_types");
foreach ($columns as $column) {
    echo "- " . $column->Field . " (" . $column->Type . ")\n";
}

echo "\nEVENT TYPES FÜR ASKPROAI (Company ID 85):\n";
$eventTypes = CalcomEventType::where('company_id', 85)->get();
foreach ($eventTypes as $et) {
    echo "\nEvent Type ID: " . $et->id . "\n";
    echo "- Name: " . ($et->name ?? $et->title ?? 'N/A') . "\n";
    echo "- Slug: " . $et->slug . "\n";
    echo "- Länge: " . $et->length . " min\n";
    echo "- Aktiv: " . ($et->is_active ? 'JA' : 'NEIN') . "\n";
    echo "- Team Event: " . ($et->is_team_event ? 'JA' : 'NEIN') . "\n";
}

// Check the specific ID used by Berlin branch
echo "\nSPEZIFISCHER EVENT TYPE 2026302:\n";
$specificET = CalcomEventType::find(2026302);
if ($specificET) {
    echo "GEFUNDEN!\n";
    echo "- Company ID: " . $specificET->company_id . "\n";
    echo "- Name: " . ($specificET->name ?? 'N/A') . "\n";
} else {
    echo "NICHT GEFUNDEN! Das ist das Problem.\n";
    
    // Find alternatives
    echo "\nVERFÜGBARE EVENT TYPES:\n";
    $available = CalcomEventType::where('company_id', 85)
        ->where('is_active', true)
        ->get();
    foreach ($available as $et) {
        echo "- ID: " . $et->id . " | " . ($et->name ?? $et->slug) . "\n";
    }
}