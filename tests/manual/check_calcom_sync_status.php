<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CAL.COM SYNC STATUS FÜR ASKPROAI ===\n\n";

$branch = Branch::where('name', 'LIKE', '%Berlin%')->where('company_id', 85)->first();

// 1. Event Types
echo "1. IMPORTIERTE EVENT TYPES:\n";
$eventTypes = CalcomEventType::where('company_id', 85)->get();
foreach ($eventTypes as $et) {
    echo "\nEvent Type: " . $et->name . "\n";
    echo "- ID: " . $et->id . "\n";
    echo "- Slug: " . $et->slug . "\n";
    echo "- Team Event: " . ($et->is_team_event ? 'JA' : 'NEIN') . "\n";
    echo "- Branch ID: " . ($et->branch_id ?? 'KEINE') . "\n";
    echo "- Cal.com ID: " . ($et->calcom_event_type_id ?? $et->calcom_numeric_event_type_id ?? 'KEINE') . "\n";
    echo "- Sync Status: " . ($et->sync_status ?? 'unbekannt') . "\n";
    echo "- Last Synced: " . ($et->last_synced_at ?? 'nie') . "\n";
}

// 2. Staff mit Cal.com User ID
echo "\n\n2. MITARBEITER MIT CAL.COM VERKNÜPFUNG:\n";
$staff = Staff::whereNotNull('calcom_user_id')
    ->where(function($q) {
        $q->where('company_id', 85)
          ->orWhere('home_branch_id', '7362c5a9-7d2b-46cd-9bcb-d69f6a60c73b');
    })
    ->get();

foreach ($staff as $s) {
    echo "\n- " . $s->name . "\n";
    echo "  Cal.com User ID: " . $s->calcom_user_id . "\n";
    echo "  Company ID: " . ($s->company_id ?? 'FEHLT') . "\n";
    echo "  Branch: " . ($s->home_branch_id ?? 'KEINE') . "\n";
}

// 3. Staff Event Type Zuordnungen
echo "\n\n3. STAFF-EVENT TYPE ZUORDNUNGEN:\n";
$assignments = DB::table('staff_event_types')
    ->join('staff', 'staff_event_types.staff_id', '=', 'staff.id')
    ->join('calcom_event_types', 'staff_event_types.event_type_id', '=', 'calcom_event_types.id')
    ->where('staff.company_id', 85)
    ->select('staff.name as staff_name', 'calcom_event_types.name as event_type_name', 
             'staff_event_types.is_primary', 'staff_event_types.calcom_user_id')
    ->get();

if ($assignments->count() > 0) {
    foreach ($assignments as $a) {
        echo "- " . $a->staff_name . " → " . $a->event_type_name;
        echo " (Primary: " . ($a->is_primary ? 'JA' : 'NEIN') . ")\n";
    }
} else {
    echo "KEINE ZUORDNUNGEN GEFUNDEN!\n";
}

// 4. Import Logs
echo "\n\n4. LETZTE IMPORT-AKTIVITÄTEN:\n";
$logs = DB::table('event_type_import_logs')
    ->where('company_id', 85)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($logs->count() > 0) {
    foreach ($logs as $log) {
        echo "- " . $log->created_at . " | Status: " . $log->status . " | Events: " . $log->imported_count . "\n";
        if ($log->error_message) {
            echo "  Fehler: " . $log->error_message . "\n";
        }
    }
} else {
    echo "KEINE IMPORT-LOGS GEFUNDEN!\n";
}

// 5. Empfehlungen
echo "\n\n5. EMPFOHLENE AKTIONEN:\n";

if ($staff->where('calcom_user_id', null)->count() > 0) {
    echo "⚠️  Mitarbeiter ohne Cal.com User ID gefunden!\n";
    echo "   → Führen Sie aus: php artisan calcom:sync-staff\n";
}

if ($eventTypes->where('sync_status', '!=', 'synced')->count() > 0) {
    echo "⚠️  Nicht synchronisierte Event Types gefunden!\n";
    echo "   → Führen Sie aus: php artisan calendar:sync-event-types --branch=" . $branch->id . "\n";
}

if ($assignments->count() == 0) {
    echo "⚠️  Keine Staff-Event Type Zuordnungen!\n";
    echo "   → Nutzen Sie: Admin Panel → Personal & Teams → Mitarbeiter-Event-Zuordnung\n";
}

// 6. Test API Verbindung
echo "\n\n6. CAL.COM API TEST:\n";
try {
    $calcomService = new \App\Services\CalcomV2Service();
    $user = $calcomService->getCurrentUser();
    echo "✅ API Verbindung erfolgreich!\n";
    echo "   Eingeloggt als: " . ($user['username'] ?? 'unbekannt') . "\n";
} catch (\Exception $e) {
    echo "❌ API Verbindung fehlgeschlagen: " . $e->getMessage() . "\n";
}