<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

echo "=== ULTRATHINK DUPLICATE CALLS ANALYSE ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 70) . "\n\n";

// 1. AKTUELLE CALLS ANALYSE
echo "1. AKTUELLE CALLS IN DER DATENBANK\n";
echo str_repeat("-", 50) . "\n";

$recentCalls = DB::table('calls')
    ->where('created_at', '>', now()->subMinutes(30))
    ->orderBy('created_at', 'desc')
    ->get();

echo "Calls in den letzten 30 Minuten: " . $recentCalls->count() . "\n\n";

// Gruppiere nach ähnlichen Zeiten (innerhalb von 5 Minuten)
$callGroups = [];
foreach ($recentCalls as $call) {
    $timestamp = strtotime($call->created_at);
    $found = false;
    
    foreach ($callGroups as $groupTime => &$group) {
        if (abs($timestamp - $groupTime) < 300) { // 5 Minuten
            $group[] = $call;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $callGroups[$timestamp] = [$call];
    }
}

// Zeige Gruppen
foreach ($callGroups as $groupTime => $calls) {
    if (count($calls) > 1) {
        echo "⚠️ GRUPPE MIT " . count($calls) . " CALLS (um " . date('H:i:s', $groupTime) . "):\n";
        
        foreach ($calls as $call) {
            echo sprintf(
                "   - %s | %s | %s | %s | Von: %s\n",
                substr($call->call_id, -12),
                $call->created_at,
                str_pad($call->call_status, 11),
                str_pad($call->duration_sec . 's', 5),
                $call->from_number ?? 'NULL'
            );
        }
        
        // Prüfe ob es derselbe Anruf ist
        $fromNumbers = array_unique(array_map(fn($c) => $c->from_number, $calls));
        $durations = array_unique(array_map(fn($c) => $c->duration_sec, $calls));
        
        if (count($fromNumbers) === 1) {
            echo "   → Vermutlich DERSELBE Anruf (gleiche Telefonnummer)\n";
        }
        echo "\n";
    }
}

// 2. IN_PROGRESS CALLS
echo "\n2. CALLS MIT STATUS 'IN_PROGRESS'\n";
echo str_repeat("-", 50) . "\n";

$inProgressCalls = DB::table('calls')
    ->where('call_status', 'in_progress')
    ->orderBy('created_at', 'desc')
    ->get();

echo "In Progress Calls: " . $inProgressCalls->count() . "\n";
foreach ($inProgressCalls as $call) {
    $age = now()->diffInMinutes($call->created_at);
    echo sprintf(
        "- %s | Erstellt vor %d Min | Von: %s | Branch: %s\n",
        substr($call->call_id, -20),
        $age,
        $call->from_number ?? 'NULL',
        substr($call->branch_id, 0, 8)
    );
    
    if ($age > 15) {
        echo "  ⚠️ Sollte bereinigt werden (älter als 15 Minuten)\n";
    }
}

// 3. WEBHOOK EVENTS ANALYSE
echo "\n3. WEBHOOK EVENTS ANALYSE\n";
echo str_repeat("-", 50) . "\n";

$webhookEvents = DB::table('webhook_events')
    ->where('created_at', '>', now()->subMinutes(30))
    ->orderBy('created_at', 'desc')
    ->get();

// Gruppiere nach event_id
$eventGroups = [];
foreach ($webhookEvents as $event) {
    if (!isset($eventGroups[$event->event_id])) {
        $eventGroups[$event->event_id] = [];
    }
    $eventGroups[$event->event_id][] = $event;
}

foreach ($eventGroups as $eventId => $events) {
    if (count($events) > 2) { // Mehr als start/end
        echo "⚠️ Call $eventId hat " . count($events) . " Events:\n";
        foreach ($events as $event) {
            echo "   - " . $event->event_type . " um " . $event->created_at . "\n";
        }
    }
}

// 4. RETELL API CHECK
echo "\n4. RETELL API - ECHTER ZUSTAND\n";
echo str_repeat("-", 50) . "\n";

$apiKey = config('services.retell.api_key');
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->post('https://api.retellai.com/v2/list-calls', [
    'limit' => 5,
    'sort_order' => 'descending'
]);

if ($response->successful()) {
    $retellCalls = $response->json();
    
    foreach ($retellCalls as $call) {
        $time = date('H:i:s', $call['start_timestamp'] / 1000);
        $callId = $call['call_id'];
        
        // Zähle wie oft dieser Call in unserer DB ist
        $dbCount = DB::table('calls')
            ->where('call_id', $callId)
            ->orWhere('retell_call_id', $callId)
            ->count();
            
        echo "- $time | " . substr($callId, -12) . " | " . $call['call_status'] . " | ";
        
        if ($dbCount === 0) {
            echo "❌ Nicht in DB\n";
        } elseif ($dbCount === 1) {
            echo "✅ 1x in DB\n";
        } else {
            echo "⚠️ {$dbCount}x in DB (DUPLIKAT!)\n";
        }
    }
}

// 5. PROBLEM DIAGNOSE
echo "\n5. PROBLEM DIAGNOSE\n";
echo str_repeat("-", 50) . "\n";

// Prüfe auf fehlende Daten
$callsWithMissingData = DB::table('calls')
    ->where('created_at', '>', now()->subHour())
    ->where(function($query) {
        $query->whereNull('from_number')
              ->orWhere('from_number', '')
              ->orWhere('from_number', 'unknown')
              ->orWhereNull('transcript')
              ->orWhere('duration_sec', 0);
    })
    ->count();

echo "Calls mit fehlenden Daten: $callsWithMissingData\n";

// Prüfe Branch IDs
$systemDefaultCalls = DB::table('calls')
    ->where('branch_id', '00000000-0000-0000-0000-000000000001')
    ->where('created_at', '>', now()->subHour())
    ->count();

echo "Calls mit System Default Branch: $systemDefaultCalls\n";

// 6. EMPFOHLENE FIXES
echo "\n6. EMPFOHLENE MASSNAHMEN\n";
echo str_repeat("-", 50) . "\n";

$problems = [];

if ($inProgressCalls->count() > 1) {
    $problems[] = "Mehrere in_progress Calls → Alte bereinigen";
}

if ($systemDefaultCalls > 0) {
    $problems[] = "System Default Branch verwendet → Branch-Zuordnung fixen";
}

if ($callsWithMissingData > 0) {
    $problems[] = "Fehlende Daten → Webhook-Datenverarbeitung prüfen";
}

foreach ($callGroups as $calls) {
    if (count($calls) > 1) {
        $problems[] = "Duplicate Calls → Idempotenz-Problem im Webhook";
        break;
    }
}

if (empty($problems)) {
    echo "✅ Keine kritischen Probleme gefunden\n";
} else {
    foreach ($problems as $i => $problem) {
        echo ($i + 1) . ". $problem\n";
    }
}

echo "\n";