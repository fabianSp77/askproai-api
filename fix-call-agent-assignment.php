<?php

/**
 * Weist Calls den richtigen Agents zu
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\PhoneNumber;
use Carbon\Carbon;

echo "\n=== Fix Call Agent Assignment ===\n\n";

// 1. Prüfe aktuelle Situation
echo "1. Aktuelle Call-Statistik:\n";
$totalCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', 1)
    ->count();
    
$callsWithAgent = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', 1)
    ->whereNotNull('retell_agent_id')
    ->count();
    
$callsToday = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', 1)
    ->whereDate('start_timestamp', Carbon::today())
    ->count();

echo "   Gesamt Calls: $totalCalls\n";
echo "   Calls mit Agent ID: $callsWithAgent\n";
echo "   Calls ohne Agent ID: " . ($totalCalls - $callsWithAgent) . "\n";
echo "   Calls heute: $callsToday\n\n";

// 2. Hole Phone Number Mapping
echo "2. Phone Number → Agent Mapping:\n";
$phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', 1)
    ->whereNotNull('retell_agent_id')
    ->get();

foreach ($phoneNumbers as $phone) {
    echo "   {$phone->number} → {$phone->retell_agent_id}\n";
}

// 3. Update Calls basierend auf to_number
echo "\n3. Update Calls mit Agent ID...\n";
$updated = 0;

foreach ($phoneNumbers as $phone) {
    // Normalisiere Nummer für Vergleich
    $phoneVariants = [
        $phone->number,
        str_replace('+49', '0', $phone->number),
        str_replace('+49', '0049', $phone->number),
    ];
    
    $count = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', 1)
        ->whereNull('retell_agent_id')
        ->where(function($query) use ($phoneVariants) {
            foreach ($phoneVariants as $variant) {
                $query->orWhere('to_number', $variant);
            }
        })
        ->update(['retell_agent_id' => $phone->retell_agent_id]);
    
    $updated += $count;
    if ($count > 0) {
        echo "   ✓ {$phone->number}: $count Calls aktualisiert\n";
    }
}

// 4. Spezial-Update für Agent ID im agent_id Feld
echo "\n4. Update Calls mit agent_id Feld...\n";
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';
$count = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', 1)
    ->whereNull('retell_agent_id')
    ->where('agent_id', $agentId)
    ->update(['retell_agent_id' => $agentId]);

if ($count > 0) {
    $updated += $count;
    echo "   ✓ Agent ID $agentId: $count Calls aktualisiert\n";
}

// 5. Finale Statistik
echo "\n5. Ergebnis:\n";
echo "   ✅ Insgesamt aktualisiert: $updated Calls\n";

// Prüfe neue Statistik
$callsWithAgentNow = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', 1)
    ->whereNotNull('retell_agent_id')
    ->count();

$callsTodayWithAgent = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', 1)
    ->whereDate('start_timestamp', Carbon::today())
    ->where('retell_agent_id', $agentId)
    ->count();

echo "   Calls mit Agent ID jetzt: $callsWithAgentNow\n";
echo "   Calls heute mit Agent: $callsTodayWithAgent\n";

echo "\n✅ Agent-Zuweisung abgeschlossen!\n";
echo "Die Statistiken sollten jetzt in der UI angezeigt werden.\n";