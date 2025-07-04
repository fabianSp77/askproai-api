<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;

echo "=== Analyse Ihres Testanrufs ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n\n";

$callId = 'call_a35bde73a77ba58f6a3ea97f75a';

// Get API key and fetch call details directly from Retell
$apiKey = config('services.retell.api_key') ?? env('RETELL_TOKEN');
if (!$apiKey) {
    $envContent = file_get_contents(__DIR__ . '/.env');
    if (preg_match('/RETELL_TOKEN=(.+)/', $envContent, $matches)) {
        $apiKey = trim($matches[1]);
    }
}

echo "📞 Hole Details von Retell API für Call ID: $callId\n";
echo str_repeat("-", 50) . "\n";

$ch = curl_init('https://api.retellai.com/v2/get-call/' . $callId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $callData = json_decode($response, true);
    
    echo "\n✅ ANRUF-DETAILS:\n";
    echo "- Von: " . ($callData['from_number'] ?? 'Unknown') . "\n";
    echo "- An: " . ($callData['to_number'] ?? 'Unknown') . "\n";
    echo "- Status: " . ($callData['status'] ?? 'Unknown') . "\n";
    echo "- Start: " . date('Y-m-d H:i:s', ($callData['start_timestamp'] ?? 0) / 1000) . " (Berliner Zeit)\n";
    echo "- Ende: " . date('Y-m-d H:i:s', ($callData['end_timestamp'] ?? 0) / 1000) . " (Berliner Zeit)\n";
    echo "- Dauer: " . ($callData['duration'] ?? 0) . " Sekunden\n";
    echo "- Agent: " . ($callData['agent_id'] ?? 'Unknown') . "\n";
    
    if (!empty($callData['transcript'])) {
        echo "\n📝 TRANSCRIPT (erste 1000 Zeichen):\n";
        echo str_repeat("-", 30) . "\n";
        echo substr($callData['transcript'], 0, 1000) . "...\n";
    }
    
    if (!empty($callData['call_analysis'])) {
        echo "\n🔍 ANRUF-ANALYSE:\n";
        echo str_repeat("-", 30) . "\n";
        
        if (isset($callData['call_analysis']['call_summary'])) {
            echo "Zusammenfassung: " . $callData['call_analysis']['call_summary'] . "\n";
        }
        
        if (isset($callData['call_analysis']['custom_analysis_data'])) {
            echo "\nExtrahierte Daten:\n";
            $customData = $callData['call_analysis']['custom_analysis_data'];
            print_r($customData);
        }
    }
    
    // Check if call is in database
    echo "\n💾 DATENBANK-STATUS:\n";
    echo str_repeat("-", 30) . "\n";
    
    // Bypass tenant scope for this check
    $callInDb = \DB::table('calls')->where('call_id', $callId)->first();
    
    if ($callInDb) {
        echo "✅ Anruf ist in der Datenbank gespeichert\n";
        echo "- DB ID: " . $callInDb->id . "\n";
        echo "- Erstellt: " . $callInDb->created_at . "\n";
        echo "- Company ID: " . $callInDb->company_id . "\n";
        echo "- Branch ID: " . $callInDb->branch_id . "\n";
    } else {
        echo "⏳ Anruf noch nicht in Datenbank\n";
        
        // Check queue
        $pendingJobs = \DB::table('jobs')->where('payload', 'like', '%' . $callId . '%')->count();
        echo "- Jobs in Queue mit dieser Call ID: " . $pendingJobs . "\n";
        
        if ($pendingJobs == 0) {
            echo "- ℹ️  Möglicherweise wartet der Anruf noch auf Webhook-Verarbeitung\n";
        }
    }
    
    // Save full data for debugging
    file_put_contents('/tmp/test-call-data.json', json_encode($callData, JSON_PRETTY_PRINT));
    echo "\n💾 Vollständige Daten gespeichert in: /tmp/test-call-data.json\n";
    
} else {
    echo "❌ Fehler beim Abrufen von Retell API: HTTP $httpCode\n";
    echo "Response: " . $response . "\n";
}

echo "\n=== Ende der Analyse ===\n";