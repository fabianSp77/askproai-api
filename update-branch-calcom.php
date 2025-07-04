<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Company;

echo "🔧 UPDATE CAL.COM KONFIGURATION DIREKT IN DER DATENBANK\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Update Branch mit Cal.com Event Type ID
$branch = Branch::withoutGlobalScopes()->where('id', 1)->first();

if ($branch) {
    echo "📍 Branch gefunden: " . $branch->name . "\n";
    echo "Aktuelle Cal.com Event Type ID: " . ($branch->calcom_event_type_id ?? 'KEINE') . "\n";
    
    // Setze die Event Type ID aus der .env
    $eventTypeId = 2026979; // Aus .env Zeile 23
    
    $branch->calcom_event_type_id = $eventTypeId;
    $branch->save();
    
    echo "✅ Cal.com Event Type ID gesetzt: $eventTypeId\n\n";
} else {
    echo "❌ Branch mit ID 1 nicht gefunden!\n\n";
}

// 2. Prüfe Company Cal.com API Key
$company = Company::withoutGlobalScopes()->first();

if ($company) {
    echo "🏢 Company: " . $company->name . "\n";
    echo "Cal.com API Key vorhanden: " . ($company->calcom_api_key ? 'JA' : 'NEIN') . "\n";
    
    if (!$company->calcom_api_key) {
        // Setze den API Key aus der .env
        $apiKey = 'cal_live_bd7aedbdf12085c5312c79ba73585920'; // Aus .env Zeile 19
        
        $company->calcom_api_key = $apiKey;
        $company->save();
        
        echo "✅ Cal.com API Key gesetzt\n";
    } else {
        echo "✅ API Key bereits vorhanden\n";
    }
}

echo "\n🎯 KONFIGURATION ABGESCHLOSSEN!\n";
echo str_repeat("=", 60) . "\n";
echo "Cal.com ist jetzt konfiguriert:\n";
echo "- Branch hat Event Type ID: $eventTypeId\n";
echo "- Company hat API Key\n";
echo "\nTerminbuchungen sollten jetzt funktionieren!\n";