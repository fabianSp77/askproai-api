#!/usr/bin/env php
<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Call;
use App\Services\RetellV2Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "=== Direct Call Import ===\n";
echo "Datum: " . date('Y-m-d H:i:s') . "\n\n";

$callId = 'call_74a8601642e8254b18597759a8e';

// Get company
$company = Company::withoutGlobalScopes()->first();
if (!$company) {
    echo "❌ Keine Company gefunden!\n";
    exit(1);
}

echo "Company: " . $company->name . " (ID: " . $company->id . ")\n\n";

// Check if call exists using raw query
$existingCall = DB::table('calls')->where('call_id', $callId)->first();
if ($existingCall) {
    echo "✅ Anruf bereits in Datenbank vorhanden!\n";
    exit(0);
}

echo "Hole Anruf von Retell API...\n";

$retellService = new RetellV2Service($company->retell_api_key);

try {
    // Get call details
    $callData = $retellService->getCall($callId);
    
    if ($callData) {
        echo "✅ Anruf gefunden:\n";
        $startTime = Carbon::createFromTimestampMs($callData['start_timestamp']);
        $endTime = isset($callData['end_timestamp']) ? Carbon::createFromTimestampMs($callData['end_timestamp']) : null;
        
        echo "- Von: " . ($callData['from_number'] ?? 'anonymous') . "\n";
        echo "- Start: " . $startTime->format('Y-m-d H:i:s') . "\n";
        echo "- Status: " . $callData['call_status'] . "\n\n";
        
        // Insert directly into database
        echo "Füge Anruf direkt in Datenbank ein...\n";
        
        $insertData = [
            'call_id' => $callData['call_id'],
            'company_id' => $company->id,
            'branch_id' => null, // Will be resolved later
            'from_number' => $callData['from_number'] ?? null,
            'to_number' => $callData['to_number'] ?? null,
            'status' => $callData['call_status'] ?? 'unknown',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_sec' => $endTime ? $startTime->diffInSeconds($endTime) : 0,
            'retell_llm_id' => $callData['retell_llm_id'] ?? null,
            'agent_id' => $callData['agent_id'] ?? null,
            'metadata' => json_encode($callData['metadata'] ?? []),
            'public_log_url' => $callData['public_log_url'] ?? null,
            'recording_url' => $callData['recording_url'] ?? null,
            'summary' => $callData['call_analysis']['summary'] ?? null,
            'raw_data' => json_encode($callData),
            'created_at' => now(),
            'updated_at' => now()
        ];
        
        $id = DB::table('calls')->insertGetId($insertData);
        
        if ($id) {
            echo "✅ Anruf erfolgreich eingefügt! ID: $id\n";
            
            // Verify
            $verifyCall = DB::table('calls')->find($id);
            if ($verifyCall) {
                echo "\nVerifizierung:\n";
                echo "- DB ID: " . $verifyCall->id . "\n";
                echo "- Call ID: " . $verifyCall->call_id . "\n";
                echo "- Created: " . $verifyCall->created_at . "\n";
            }
        } else {
            echo "❌ Fehler beim Einfügen!\n";
        }
        
    } else {
        echo "❌ Anruf nicht gefunden in Retell API!\n";
    }
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}

echo "\n=== Import abgeschlossen ===\n";