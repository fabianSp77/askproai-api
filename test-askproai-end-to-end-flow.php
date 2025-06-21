<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "\n=== ASKPROAI END-TO-END TELEFONIE & TERMINBUCHUNG TEST ===\n\n";

// Step 1: Setup Test Data
echo "SCHRITT 1: TEST-DATEN SETUP\n";
echo "===========================\n\n";

try {
    // Check if AskProAI company exists
    $company = DB::table('companies')->where('name', 'LIKE', '%AskProAI%')->first();
    
    if (!$company) {
        echo "✗ Keine AskProAI Firma gefunden - erstelle neue...\n";
        $companyId = DB::table('companies')->insertGetId([
            'name' => 'AskProAI GmbH',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $company = DB::table('companies')->find($companyId);
    }
    
    echo "✓ Firma: {$company->name} (ID: {$company->id})\n";
    
    // Check for API keys
    $needsUpdate = false;
    $updates = [];
    
    if (!$company->retell_api_key) {
        $retellKey = config('services.retell.api_key') ?? config('services.retell.token');
        if ($retellKey) {
            $updates['retell_api_key'] = encrypt($retellKey);
            echo "  → Setze Retell API Key aus Umgebung\n";
            $needsUpdate = true;
        } else {
            echo "  ✗ Kein Retell API Key in Umgebung gefunden\n";
        }
    } else {
        echo "  ✓ Retell API Key bereits gesetzt\n";
    }
    
    if (!$company->calcom_api_key) {
        $calcomKey = config('services.calcom.api_key');
        if ($calcomKey) {
            $updates['calcom_api_key'] = encrypt($calcomKey);
            echo "  → Setze Cal.com API Key aus Umgebung\n";
            $needsUpdate = true;
        } else {
            echo "  ✗ Kein Cal.com API Key in Umgebung gefunden\n";
        }
    } else {
        echo "  ✓ Cal.com API Key bereits gesetzt\n";
    }
    
    if ($needsUpdate) {
        DB::table('companies')->where('id', $company->id)->update($updates);
    }
    
    // Check branch
    $branch = DB::table('branches')->where('company_id', $company->id)->first();
    if (!$branch) {
        echo "\n✗ Keine Filiale gefunden - erstelle neue...\n";
        $branchId = Str::uuid()->toString();
        DB::table('branches')->insert([
            'id' => $branchId,
            'company_id' => $company->id,
            'name' => 'AskProAI Berlin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $branch = DB::table('branches')->where('id', $branchId)->first();
    }
    
    echo "\n✓ Filiale: {$branch->name} (ID: {$branch->id})\n";
    
    // Setup phone number
    $testPhone = '+493083793369';
    $phoneExists = DB::table('phone_numbers')
        ->where('branch_id', $branch->id)
        ->where('number', $testPhone)
        ->exists();
        
    if (!$phoneExists) {
        echo "  → Füge Telefonnummer hinzu: $testPhone\n";
        DB::table('phone_numbers')->insert([
            'id' => Str::uuid()->toString(),
            'branch_id' => $branch->id,
            'number' => $testPhone,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    } else {
        echo "  ✓ Telefonnummer bereits konfiguriert: $testPhone\n";
    }
    
    // Check Cal.com event type
    if (!$branch->calcom_event_type_id) {
        echo "\n✗ Keine Cal.com Event Type ID gesetzt\n";
        echo "  → Bitte manuell setzen oder Event Type Import Wizard verwenden\n";
        
        // Try to find existing event type
        $eventType = DB::table('calcom_event_types')
            ->where('company_id', $company->id)
            ->first();
            
        if ($eventType) {
            DB::table('branches')
                ->where('id', $branch->id)
                ->update(['calcom_event_type_id' => $eventType->calcom_numeric_event_type_id ?? $eventType->id]);
            echo "  → Event Type gefunden und zugewiesen: {$eventType->name}\n";
        }
    } else {
        echo "  ✓ Cal.com Event Type ID: {$branch->calcom_event_type_id}\n";
    }
    
    // Set Retell Agent ID if missing
    if (!$branch->retell_agent_id) {
        $defaultAgentId = config('services.retell.default_agent_id');
        if ($defaultAgentId) {
            DB::table('branches')
                ->where('id', $branch->id)
                ->update(['retell_agent_id' => $defaultAgentId]);
            echo "  → Retell Agent ID gesetzt: $defaultAgentId\n";
        }
    } else {
        echo "  ✓ Retell Agent ID: {$branch->retell_agent_id}\n";
    }
    
} catch (Exception $e) {
    echo "✗ Fehler beim Setup: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: Test Phone Resolution
echo "\n\nSCHRITT 2: TELEFONNUMMER → FILIALE ZUORDNUNG\n";
echo "============================================\n\n";

try {
    // Test WebhookMCPServer phone resolution
    $webhookMCP = app(\App\Services\MCP\WebhookMCPServer::class);
    
    // Create reflection to test protected method
    $reflection = new ReflectionClass($webhookMCP);
    $method = $reflection->getMethod('resolvePhoneNumber');
    $method->setAccessible(true);
    
    $resolution = $method->invoke($webhookMCP, $testPhone);
    
    echo "Phone Resolution Test für $testPhone:\n";
    echo json_encode($resolution, JSON_PRETTY_PRINT) . "\n";
    
    if ($resolution['success']) {
        echo "\n✓ Telefonnummer erfolgreich aufgelöst:\n";
        echo "  - Filiale ID: {$resolution['branch_id']}\n";
        echo "  - Firma ID: {$resolution['company_id']}\n";
        echo "  - Filiale Name: {$resolution['branch_name']}\n";
        echo "  - Cal.com Event Type: " . ($resolution['calcom_event_type_id'] ?? 'Nicht gesetzt') . "\n";
    } else {
        echo "\n✗ Telefonnummer konnte nicht aufgelöst werden\n";
        echo "  Fehler: {$resolution['message']}\n";
    }
    
} catch (Exception $e) {
    echo "✗ Fehler bei Phone Resolution: " . $e->getMessage() . "\n";
}

// Step 3: Simulate Webhook Call
echo "\n\nSCHRITT 3: WEBHOOK VERARBEITUNG SIMULATION\n";
echo "==========================================\n\n";

try {
    // Create test webhook payload
    $webhookPayload = [
        'event' => 'call_ended',
        'call' => [
            'call_id' => 'test_' . Str::random(16),
            'agent_id' => $branch->retell_agent_id ?? 'agent_test',
            'from_number' => '+491234567890',
            'to_number' => $testPhone,
            'call_type' => 'inbound',
            'call_status' => 'ended',
            'start_timestamp' => Carbon::now()->subMinutes(5)->timestamp * 1000,
            'end_timestamp' => Carbon::now()->timestamp * 1000,
            'duration_ms' => 300000, // 5 minutes
            'retell_llm_dynamic_variables' => [
                'name' => 'Max Mustermann',
                'datum' => Carbon::tomorrow()->format('Y-m-d'),
                'uhrzeit' => '14:00',
                'dienstleistung' => 'Beratung',
                'booking_confirmed' => true
            ],
            'call_analysis' => [
                'call_summary' => 'Kunde möchte einen Beratungstermin buchen',
                'sentiment' => 'positive',
                'custom_analysis_data' => [
                    '_email' => 'max.mustermann@example.com'
                ]
            ]
        ]
    ];
    
    echo "Webhook Payload:\n";
    echo "- Event: {$webhookPayload['event']}\n";
    echo "- Call ID: {$webhookPayload['call']['call_id']}\n";
    echo "- Von: {$webhookPayload['call']['from_number']}\n";
    echo "- An: {$webhookPayload['call']['to_number']}\n";
    echo "- Booking Confirmed: " . ($webhookPayload['call']['retell_llm_dynamic_variables']['booking_confirmed'] ? 'Ja' : 'Nein') . "\n";
    echo "- Datum: {$webhookPayload['call']['retell_llm_dynamic_variables']['datum']}\n";
    echo "- Uhrzeit: {$webhookPayload['call']['retell_llm_dynamic_variables']['uhrzeit']}\n";
    
    // Process webhook
    echo "\nVerarbeite Webhook...\n";
    $result = $webhookMCP->processRetellWebhook($webhookPayload);
    
    echo "\nErgebnis:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    if ($result['success']) {
        echo "\n✓ Webhook erfolgreich verarbeitet!\n";
        echo "  - Call ID (DB): {$result['call_id']}\n";
        echo "  - Customer ID: {$result['customer_id']}\n";
        echo "  - Termin erstellt: " . ($result['appointment_created'] ? 'Ja' : 'Nein') . "\n";
        
        if ($result['appointment_created']) {
            echo "  - Termin Details: " . json_encode($result['appointment_data']) . "\n";
        }
    } else {
        echo "\n✗ Webhook Verarbeitung fehlgeschlagen\n";
        echo "  Fehler: {$result['message']}\n";
    }
    
} catch (Exception $e) {
    echo "✗ Fehler bei Webhook Verarbeitung: " . $e->getMessage() . "\n";
    echo "  Stack Trace: " . $e->getTraceAsString() . "\n";
}

// Step 4: Check Database Results
echo "\n\nSCHRITT 4: DATENBANK ÜBERPRÜFUNG\n";
echo "==================================\n\n";

try {
    // Check calls table
    $latestCall = DB::table('calls')
        ->where('company_id', $company->id)
        ->orderBy('created_at', 'desc')
        ->first();
        
    if ($latestCall) {
        echo "Letzter Anruf:\n";
        echo "- ID: {$latestCall->id}\n";
        echo "- Retell Call ID: {$latestCall->retell_call_id}\n";
        echo "- Branch ID: " . ($latestCall->branch_id ?? 'Nicht zugeordnet') . "\n";
        echo "- Customer ID: " . ($latestCall->customer_id ?? 'Kein Kunde') . "\n";
        echo "- Appointment ID: " . ($latestCall->appointment_id ?? 'Kein Termin') . "\n";
        echo "- Erstellt: {$latestCall->created_at}\n";
        
        // Check customer
        if ($latestCall->customer_id) {
            $customer = DB::table('customers')->find($latestCall->customer_id);
            if ($customer) {
                echo "\nKunde:\n";
                echo "- Name: {$customer->name}\n";
                echo "- Telefon: {$customer->phone}\n";
                echo "- Email: " . ($customer->email ?? 'Nicht angegeben') . "\n";
            }
        }
        
        // Check appointment
        if ($latestCall->appointment_id) {
            $appointment = DB::table('appointments')->find($latestCall->appointment_id);
            if ($appointment) {
                echo "\nTermin:\n";
                echo "- ID: {$appointment->id}\n";
                echo "- Start: {$appointment->starts_at}\n";
                echo "- Ende: {$appointment->ends_at}\n";
                echo "- Status: {$appointment->status}\n";
                echo "- Cal.com Booking ID: " . ($appointment->calcom_booking_id ?? 'Nicht synchronisiert') . "\n";
            }
        }
    } else {
        echo "Keine Anrufe in der Datenbank gefunden.\n";
    }
    
} catch (Exception $e) {
    echo "✗ Fehler bei Datenbankprüfung: " . $e->getMessage() . "\n";
}

// Step 5: Test Cal.com Integration
echo "\n\nSCHRITT 5: CAL.COM INTEGRATION TEST\n";
echo "=====================================\n\n";

try {
    $calcomMCP = app(\App\Services\MCP\CalcomMCPServer::class);
    
    // Test connection
    echo "Teste Cal.com Verbindung...\n";
    $connectionResult = $calcomMCP->testConnection(['company_id' => $company->id]);
    
    if ($connectionResult['connected'] ?? false) {
        echo "✓ Cal.com Verbindung erfolgreich\n";
        echo "  - User: " . json_encode($connectionResult['user'] ?? []) . "\n";
    } else {
        echo "✗ Cal.com Verbindung fehlgeschlagen\n";
        echo "  - Fehler: " . ($connectionResult['message'] ?? 'Unbekannt') . "\n";
    }
    
    // Get event types
    echo "\nLade Event Types...\n";
    $eventTypesResult = $calcomMCP->getEventTypes(['company_id' => $company->id]);
    
    if (!isset($eventTypesResult['error'])) {
        echo "✓ {$eventTypesResult['count']} Event Types gefunden\n";
        if ($eventTypesResult['count'] > 0) {
            foreach (array_slice($eventTypesResult['event_types'], 0, 3) as $et) {
                echo "  - {$et['title']} (ID: {$et['id']}, Dauer: {$et['length']} min)\n";
            }
        }
    } else {
        echo "✗ Event Types konnten nicht geladen werden\n";
        echo "  - Fehler: {$eventTypesResult['error']}\n";
    }
    
} catch (Exception $e) {
    echo "✗ Fehler bei Cal.com Test: " . $e->getMessage() . "\n";
}

// Step 6: Summary and Recommendations
echo "\n\nZUSAMMENFASSUNG & EMPFEHLUNGEN\n";
echo "===============================\n\n";

$checklist = [
    'company_setup' => [
        'label' => 'Firma Setup',
        'status' => $company ? true : false,
        'note' => $company ? "ID: {$company->id}" : 'Nicht gefunden'
    ],
    'retell_api_key' => [
        'label' => 'Retell API Key',
        'status' => $company && $company->retell_api_key ? true : false,
        'note' => 'In Firma Einstellungen'
    ],
    'calcom_api_key' => [
        'label' => 'Cal.com API Key',
        'status' => $company && $company->calcom_api_key ? true : false,
        'note' => 'In Firma Einstellungen'
    ],
    'branch_setup' => [
        'label' => 'Filiale Setup',
        'status' => $branch ? true : false,
        'note' => $branch ? "ID: {$branch->id}" : 'Nicht gefunden'
    ],
    'phone_number' => [
        'label' => 'Telefonnummer',
        'status' => DB::table('phone_numbers')->where('branch_id', $branch->id ?? '')->exists(),
        'note' => $testPhone
    ],
    'calcom_event_type' => [
        'label' => 'Cal.com Event Type',
        'status' => $branch && $branch->calcom_event_type_id ? true : false,
        'note' => $branch && $branch->calcom_event_type_id ? "ID: {$branch->calcom_event_type_id}" : 'Nicht zugewiesen'
    ],
    'retell_agent' => [
        'label' => 'Retell Agent',
        'status' => $branch && $branch->retell_agent_id ? true : false,
        'note' => $branch && $branch->retell_agent_id ? "ID: {$branch->retell_agent_id}" : 'Nicht zugewiesen'
    ]
];

echo "Status Checklist:\n";
foreach ($checklist as $key => $item) {
    $icon = $item['status'] ? '✓' : '✗';
    echo "  $icon {$item['label']}: {$item['note']}\n";
}

$readyCount = count(array_filter($checklist, fn($item) => $item['status']));
$totalCount = count($checklist);

echo "\nBereitschaft: $readyCount/$totalCount\n";

if ($readyCount < $totalCount) {
    echo "\nNächste Schritte:\n";
    foreach ($checklist as $key => $item) {
        if (!$item['status']) {
            switch ($key) {
                case 'retell_api_key':
                    echo "  1. Retell API Key in .env setzen: DEFAULT_RETELL_API_KEY=xxx\n";
                    break;
                case 'calcom_api_key':
                    echo "  2. Cal.com API Key in .env setzen: DEFAULT_CALCOM_API_KEY=xxx\n";
                    break;
                case 'calcom_event_type':
                    echo "  3. Cal.com Event Type importieren: /admin/calcom-event-types\n";
                    break;
                case 'retell_agent':
                    echo "  4. Retell Agent ID in Filiale setzen\n";
                    break;
            }
        }
    }
} else {
    echo "\n✓ System ist bereit für End-to-End Tests!\n";
}

echo "\nTest Commands:\n";
echo "  - Webhook Test: php test-mcp-webhook-final.php\n";
echo "  - Live Monitoring: tail -f storage/logs/laravel.log | grep MCP\n";
echo "  - Dashboard: /admin\n";

echo "\n=== TEST ABGESCHLOSSEN ===\n";