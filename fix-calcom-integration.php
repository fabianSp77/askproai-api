<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Company;
use App\Services\CalcomV2Service;

$company = Company::first();
$branch = Branch::withoutGlobalScopes()->first();

echo "🔧 REPARIERE CAL.COM INTEGRATION\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Prüfe Cal.com Event Types
echo "1. Hole verfügbare Cal.com Event Types...\n";

try {
    $calcomService = new CalcomV2Service($company->calcom_api_key);
    
    // Get user info first
    $user = $calcomService->getMe();
    if (isset($user['data'])) {
        echo "✅ Cal.com Verbindung funktioniert!\n";
        echo "User: " . ($user['data']['email'] ?? 'Unknown') . "\n\n";
    }
    
    // Get event types
    $eventTypes = $calcomService->getEventTypes();
    
    if (isset($eventTypes['data']) && count($eventTypes['data']) > 0) {
        echo "📅 Verfügbare Event Types:\n";
        foreach ($eventTypes['data'] as $eventType) {
            echo "  - ID: " . $eventType['id'] . "\n";
            echo "    Title: " . $eventType['title'] . "\n";
            echo "    Slug: " . $eventType['slug'] . "\n";
            echo "    Length: " . $eventType['length'] . " min\n";
            echo "    Active: " . ($eventType['hidden'] ? 'Nein' : 'Ja') . "\n\n";
        }
        
        // Setze den ersten aktiven Event Type für die Branch
        $activeEventType = collect($eventTypes['data'])->first(function ($et) {
            return !$et['hidden'];
        });
        
        if ($activeEventType && !$branch->calcom_event_type_id) {
            echo "➡️ Setze Event Type ID " . $activeEventType['id'] . " für Branch...\n";
            $branch->update([
                'calcom_event_type_id' => $activeEventType['id']
            ]);
            echo "✅ Branch konfiguriert!\n";
        }
        
    } else {
        echo "❌ Keine Event Types gefunden!\n";
        echo "Bitte erstelle zuerst einen Event Type in Cal.com.\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Cal.com Fehler: " . $e->getMessage() . "\n";
    
    // Prüfe ob API Key verschlüsselt ist
    try {
        $apiKey = decrypt($company->calcom_api_key);
        echo "\n🔐 Versuche mit entschlüsseltem API Key...\n";
        
        $calcomService = new CalcomV2Service($apiKey);
        $user = $calcomService->getMe();
        
        if (isset($user['data'])) {
            echo "✅ Verbindung mit entschlüsseltem Key funktioniert!\n";
        }
    } catch (\Exception $e2) {
        echo "❌ Auch mit Entschlüsselung fehlgeschlagen: " . $e2->getMessage() . "\n";
    }
}

echo "\n";