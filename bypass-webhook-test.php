<?php
// Bypass webhook and directly process the test call

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\ProcessRetellCallEndedJob;
use App\Models\Company;

echo "=== Bypass Webhook - Direkte Verarbeitung ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n\n";

$callId = 'call_a35bde73a77ba58f6a3ea97f75a';

// Get API key
$apiKey = config('services.retell.api_key') ?? env('RETELL_TOKEN');
if (!$apiKey) {
    $envContent = file_get_contents(__DIR__ . '/.env');
    if (preg_match('/RETELL_TOKEN=(.+)/', $envContent, $matches)) {
        $apiKey = trim($matches[1]);
    }
}

// Get call details from Retell
echo "1. Hole Call-Details von Retell...\n";
$ch = curl_init('https://api.retellai.com/v2/get-call/' . $callId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    echo "❌ Fehler beim Abrufen des Anrufs\n";
    exit(1);
}

$callData = json_decode($response, true);
echo "✅ Call-Daten erhalten\n\n";

// Prepare webhook payload as Retell would send it
$webhookPayload = [
    'event' => 'call_ended',
    'call' => $callData
];

// Get company
$company = Company::first();
if (!$company) {
    echo "❌ Keine Company gefunden\n";
    exit(1);
}

echo "2. Erstelle ProcessRetellCallEndedJob...\n";
echo "Company: " . $company->name . "\n";
echo "Call ID: " . $callId . "\n\n";

// Create and dispatch job
try {
    // ProcessRetellCallEndedJob expects: (array $data, ?int $webhookEventId = null, ?string $correlationId = null)
    $job = new ProcessRetellCallEndedJob($webhookPayload, null, 'test-' . uniqid());
    
    // Execute synchronously for testing
    echo "3. Führe Job synchron aus...\n";
    $job->handle();
    
    echo "\n✅ Job erfolgreich ausgeführt!\n";
    echo "Prüfen Sie jetzt die Datenbank oder das Admin-Panel.\n";
    
} catch (\Exception $e) {
    echo "\n❌ Fehler bei der Verarbeitung:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Check if call is now in database
echo "\n4. Prüfe Datenbank...\n";
$callInDb = \DB::table('calls')->where('call_id', $callId)->first();

if ($callInDb) {
    echo "✅ Anruf in Datenbank gefunden!\n";
    echo "- ID: " . $callInDb->id . "\n";
    echo "- Status: " . $callInDb->status . "\n";
    echo "- Dauer: " . $callInDb->duration_seconds . " Sekunden\n";
    echo "- Company ID: " . $callInDb->company_id . "\n";
    echo "- Branch ID: " . $callInDb->branch_id . "\n";
} else {
    echo "❌ Anruf nicht in Datenbank\n";
}