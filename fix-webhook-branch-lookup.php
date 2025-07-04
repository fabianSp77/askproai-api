<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Call;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

echo "=== FIX WEBHOOK & IMPORT MISSING CALLS ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Prüfe Branch für Telefonnummer
echo "1. BRANCH ZUORDNUNG PRÜFEN:\n";
echo str_repeat("-", 40) . "\n";

$phoneNumber = '+493083793369';
$branches = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('phone_number', 'LIKE', '%3083793369%')
    ->get();

echo "Gefundene Branches für $phoneNumber: " . $branches->count() . "\n";
foreach ($branches as $branch) {
    echo "- Branch: " . $branch->name . " (ID: " . $branch->id . ", Company: " . $branch->company_id . ")\n";
    echo "  Phone: " . $branch->phone_number . "\n";
}

if ($branches->count() > 1) {
    echo "\n⚠️ PROBLEM: Mehrere Branches für dieselbe Nummer!\n";
    echo "Verwende erste Branch: " . $branches->first()->name . "\n";
}

// 2. Importiere fehlende Calls
echo "\n2. IMPORTIERE FEHLENDE CALLS:\n";
echo str_repeat("-", 40) . "\n";

$apiKey = config('services.retell.api_key');
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->post('https://api.retellai.com/v2/list-calls', [
    'limit' => 10,
    'sort_order' => 'descending'
]);

if ($response->successful()) {
    $calls = $response->json();
    $imported = 0;
    
    foreach ($calls as $callData) {
        $callId = $callData['call_id'];
        
        // Prüfe ob Call existiert
        $exists = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('call_id', $callId)
            ->exists();
            
        if (!$exists) {
            echo "\nImportiere Call: $callId\n";
            
            try {
                // Finde Branch
                $toNumber = $callData['to_number'] ?? $callData['to'] ?? null;
                $branch = null;
                
                if ($toNumber) {
                    $cleanNumber = preg_replace('/[^0-9+]/', '', $toNumber);
                    $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                        ->where('phone_number', 'LIKE', '%' . substr($cleanNumber, -10) . '%')
                        ->first();
                }
                
                if (!$branch) {
                    echo "  ⚠️ Keine Branch gefunden für: $toNumber\n";
                    // Verwende Default Branch
                    $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)->first();
                }
                
                if ($branch) {
                    // Erstelle Call
                    $call = new Call();
                    $call->call_id = $callId;
                    $call->retell_call_id = $callId;
                    $call->from_number = $callData['from_number'] ?? 'unknown';
                    $call->to_number = $toNumber ?? 'unknown';
                    $call->direction = 'inbound';
                    $call->call_status = $callData['call_status'] ?? 'ended';
                    $call->start_timestamp = isset($callData['start_timestamp']) 
                        ? \Carbon\Carbon::createFromTimestampMs($callData['start_timestamp']) 
                        : now();
                    $call->end_timestamp = isset($callData['end_timestamp']) 
                        ? \Carbon\Carbon::createFromTimestampMs($callData['end_timestamp']) 
                        : now();
                    $call->duration_seconds = $callData['duration'] ?? 0;
                    $call->recording_url = $callData['recording_url'] ?? null;
                    $call->transcript = $callData['transcript'] ?? null;
                    $call->agent_id = $callData['agent_id'] ?? null;
                    $call->metadata = $callData['metadata'] ?? [];
                    $call->branch_id = $branch->id;
                    $call->company_id = $branch->company_id;
                    
                    $call->save();
                    
                    echo "  ✅ Call importiert (Branch: " . $branch->name . ")\n";
                    $imported++;
                }
            } catch (\Exception $e) {
                echo "  ❌ Fehler: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\nImportierte Calls: $imported\n";
} else {
    echo "❌ Retell API Fehler: " . $response->status() . "\n";
}

// 3. Fix für Webhook Controller
echo "\n3. WEBHOOK CONTROLLER FIX:\n";
echo str_repeat("-", 40) . "\n";

$controllerPath = '/var/www/api-gateway/app/Http/Controllers/Api/RetellWebhookSimpleController.php';
$content = file_get_contents($controllerPath);

// Prüfe ob schon gefixt
if (strpos($content, 'Verwende erste gefundene Branch') === false) {
    echo "Aktualisiere Webhook Controller...\n";
    
    // Backup
    file_put_contents($controllerPath . '.backup_' . date('YmdHis'), $content);
    
    // Fix: Bei mehreren Branches erste verwenden
    $searchPattern = '/if \(\$branch\) \{[\s\S]*?\$branch = Branch::withoutGlobalScope\(TenantScope::class\)->find\(\$branch->id\);[\s\S]*?\}/';
    
    $replacement = 'if ($branch) {
                // Bei mehreren Branches für dieselbe Nummer: erste verwenden
                $allBranches = \DB::table(\'branches\')
                    ->where(\'phone_number\', \'LIKE\', \'%\' . substr($phoneNumber, -10) . \'%\')
                    ->get();
                    
                if ($allBranches->count() > 1) {
                    Log::warning(\'Mehrere Branches für Nummer gefunden\', [
                        \'phone\' => $phoneNumber,
                        \'count\' => $allBranches->count(),
                        \'using_id\' => $branch->id
                    ]);
                }
                
                // Konvertiere zu Model
                $branch = Branch::withoutGlobalScope(TenantScope::class)->find($branch->id);
            }';
    
    $newContent = preg_replace($searchPattern, $replacement, $content);
    
    if ($newContent !== $content) {
        file_put_contents($controllerPath, $newContent);
        echo "✅ Controller aktualisiert\n";
    } else {
        echo "⚠️ Controller konnte nicht aktualisiert werden\n";
    }
} else {
    echo "✅ Controller bereits gefixt\n";
}

echo "\n✅ FERTIG!\n";