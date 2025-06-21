<?php

echo "=== Testing Event Type Name Parser ===\n\n";

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

// Test Namen
$testNames = [
    "AskProAI + aus Berlin + Beratung + 30% mehr Umsatz für Sie und besten Kundenservice 24/7",
    "30 Minuten Termin mit Fabian Spitzer",
    "ModernHair - Haarschnitt Herren",
    "FitXpert München - Personal Training 60 Min",
    "Erstberatung - 15 Minuten kostenlos",
    "Check-up Termin bei Dr. Schmidt",
    "Salon Demo GmbH + Premium Styling + 45 Min"
];

// Teste beide Parser
$oldParser = new \App\Services\EventTypeNameParser();
$smartParser = new \App\Services\SmartEventTypeNameParser();

// Test-Branch
$branch = \App\Models\Branch::withoutGlobalScopes()->first();
if (!$branch) {
    echo "Keine Branch gefunden!\n";
    exit;
}

echo "Test-Branch: {$branch->name} (Company: {$branch->company->name})\n";
echo str_repeat('=', 80) . "\n\n";

foreach ($testNames as $name) {
    echo "Original: $name\n";
    echo str_repeat('-', 60) . "\n";
    
    // Old Parser
    $oldService = $oldParser->extractServiceName($name);
    $oldGenerated = $oldParser->generateEventTypeName($branch, $oldService);
    echo "Alt Parser:\n";
    echo "  Service: $oldService\n";
    echo "  Generiert: $oldGenerated\n";
    
    // Smart Parser
    $smartService = $smartParser->extractCleanServiceName($name);
    $formats = $smartParser->generateNameFormats($branch, $name);
    echo "\nSmart Parser:\n";
    echo "  Service: $smartService\n";
    echo "  Formate:\n";
    foreach ($formats as $type => $format) {
        echo "    - $type: $format\n";
    }
    
    echo "\n" . str_repeat('=', 80) . "\n\n";
}

// Test speziell für AskProAI
echo "Spezialtest für problematischen Namen:\n";
$problemName = "AskProAI + aus Berlin + Beratung + 30% mehr Umsatz für Sie und besten Kundenservice 24/7";
$cleanService = $smartParser->extractCleanServiceName($problemName);
echo "Original: $problemName\n";
echo "Extrahiert: $cleanService\n";
echo "Empfohlen: {$branch->name} - $cleanService\n";

echo "\n✅ Test abgeschlossen!\n";