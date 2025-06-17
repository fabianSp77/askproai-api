<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Branch;
use App\Models\Company;
use App\Models\CalcomEventType;
use App\Services\PhoneNumberResolver;

echo "=== ASKPROAI SYSTEM STATUS CHECK ===\n";
echo "Zeit: " . now()->format('Y-m-d H:i:s') . "\n\n";

// Check AskProAI Berlin
$branch = Branch::where('name', 'LIKE', '%Berlin%')->where('company_id', 85)->first();

if ($branch) {
    echo "✅ Filiale gefunden: {$branch->name}\n";
    echo "   - ID: {$branch->id}\n";
    echo "   - Aktiv: " . ($branch->is_active ? 'JA' : 'NEIN') . "\n";
    echo "   - Telefon: {$branch->phone_number}\n";
    echo "   - Retell Agent: " . ($branch->retell_agent_id ?: 'FEHLT') . "\n";
    echo "   - Cal.com Event Type: " . ($branch->calcom_event_type_id ?: 'FEHLT') . "\n";
    
    // Test PhoneNumberResolver
    $phoneNumber = '+493083793369';
    echo "\n📞 Test PhoneNumberResolver für: $phoneNumber\n";
    
    try {
        // Test phone number assignment
        $phoneNumberRecord = \DB::table('phone_numbers')
            ->where('number', $phoneNumber)
            ->first();
            
        if ($phoneNumberRecord) {
            echo "✅ Telefonnummer in phone_numbers Tabelle gefunden\n";
            echo "   - Branch ID: {$phoneNumberRecord->branch_id}\n";
        } else {
            echo "⚠️  Telefonnummer nicht in phone_numbers Tabelle\n";
            // Check if it's directly on branch
            $branchWithPhone = Branch::where('phone_number', $phoneNumber)->first();
            if ($branchWithPhone) {
                echo "✅ Telefonnummer direkt bei Branch gespeichert\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ Fehler beim Test: " . $e->getMessage() . "\n";
    }
    
    // Check Cal.com Event Type
    if ($branch->calcom_event_type_id) {
        $eventType = CalcomEventType::find($branch->calcom_event_type_id);
        if ($eventType) {
            echo "\n📅 Cal.com Event Type gefunden: {$eventType->name}\n";
        } else {
            echo "\n❌ Cal.com Event Type ID {$branch->calcom_event_type_id} existiert nicht!\n";
        }
    }
} else {
    echo "❌ AskProAI Berlin Filiale nicht gefunden!\n";
}

// Count services
echo "\n📊 Service-Übersicht:\n";
$serviceFiles = glob(__DIR__ . '/../app/Services/*Service.php');
echo "   - Anzahl Service-Dateien: " . count($serviceFiles) . "\n";

// List Cal.com related services
$calcomServices = array_filter($serviceFiles, function($file) {
    return stripos($file, 'calcom') !== false;
});
echo "   - Cal.com Services: " . count($calcomServices) . "\n";
foreach ($calcomServices as $service) {
    echo "     • " . basename($service) . "\n";
}

// List Retell related services
$retellServices = array_filter($serviceFiles, function($file) {
    return stripos($file, 'retell') !== false;
});
echo "   - Retell Services: " . count($retellServices) . "\n";
foreach ($retellServices as $service) {
    echo "     • " . basename($service) . "\n";
}

echo "\n=== CHECK ABGESCHLOSSEN ===\n";