<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Branch;

echo "🔧 UPDATE COMPANY CAL.COM CONFIGURATION\n";
echo str_repeat("=", 60) . "\n\n";

$company = Company::withoutGlobalScopes()->first();
$branch = Branch::withoutGlobalScopes()->first();

if (!$company) {
    echo "❌ Keine Company gefunden!\n";
    exit(1);
}

echo "🏢 Company: " . $company->name . "\n";
echo "📍 Branch: " . ($branch ? $branch->name : 'Keine Branch gefunden') . "\n\n";

// Setze die Cal.com Event Type ID auf Company-Ebene
if (!$company->calcom_event_type_id) {
    $company->calcom_event_type_id = 2026979;
    $company->save();
    echo "✅ Cal.com Event Type ID auf Company gesetzt: 2026979\n";
} else {
    echo "✅ Cal.com Event Type ID bereits vorhanden: " . $company->calcom_event_type_id . "\n";
}

// Stelle sicher, dass die Branch auf "inherit" steht
if ($branch && $branch->calendar_mode !== 'inherit') {
    $branch->calendar_mode = 'inherit';
    $branch->save();
    echo "✅ Branch Kalender-Modus auf 'inherit' gesetzt\n";
}

echo "\n📊 AKTUELLE KONFIGURATION:\n";
echo str_repeat("-", 40) . "\n";
echo "Company Cal.com API Key: " . ($company->calcom_api_key ? '✅ Vorhanden' : '❌ Fehlt') . "\n";
echo "Company Cal.com Event Type ID: " . ($company->calcom_event_type_id ?? '❌ Fehlt') . "\n";
echo "Company Cal.com Team Slug: " . ($company->calcom_team_slug ?? 'Nicht gesetzt') . "\n";

if ($branch) {
    echo "\nBranch Kalender-Modus: " . $branch->calendar_mode . "\n";
    $effectiveConfig = $branch->getEffectiveCalcomConfig();
    echo "Branch effektive Event Type ID: " . ($effectiveConfig['event_type_id'] ?? 'Keine') . "\n";
}

echo "\n✅ KONFIGURATION ABGESCHLOSSEN!\n";
echo "Die Cal.com Integration sollte jetzt funktionieren.\n";