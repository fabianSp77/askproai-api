#!/usr/bin/env php
<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Call;
use App\Services\RetellV2Service;
use App\Jobs\ProcessRetellCallEndedJob;
use Carbon\Carbon;

echo "=== Import einzelner Anruf ===\n";
echo "Datum: " . date('Y-m-d H:i:s') . "\n\n";

$callId = 'call_74a8601642e8254b18597759a8e';

// Set company context
$company = Company::first();
if (!$company) {
    echo "❌ Keine Company gefunden!\n";
    exit(1);
}

app()->instance('current_company', $company);
echo "Company: " . $company->name . "\n\n";

// Check if call already exists
if (Call::where('call_id', $callId)->exists()) {
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
        echo "- Von: " . ($callData['from_number'] ?? 'anonymous') . "\n";
        echo "- Start: " . Carbon::createFromTimestampMs($callData['start_timestamp'])->format('Y-m-d H:i:s') . "\n";
        echo "- Status: " . $callData['call_status'] . "\n\n";
        
        // Process directly without queue
        echo "Verarbeite Anruf direkt (ohne Queue)...\n";
        
        $job = new ProcessRetellCallEndedJob(
            $callData,
            $company->id,
            null // no request for direct processing
        );
        
        // Execute job directly
        $job->handle();
        
        echo "✅ Anruf wurde verarbeitet!\n";
        
        // Verify
        $call = Call::where('call_id', $callId)->first();
        if ($call) {
            echo "\n✅ Anruf erfolgreich in Datenbank gespeichert:\n";
            echo "- ID: " . $call->id . "\n";
            echo "- Created: " . $call->created_at . "\n";
        } else {
            echo "\n❌ Anruf konnte nicht in Datenbank gespeichert werden!\n";
        }
        
    } else {
        echo "❌ Anruf nicht gefunden in Retell API!\n";
    }
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Import abgeschlossen ===\n";