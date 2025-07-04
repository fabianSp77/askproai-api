<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$callId = 'call_7f570cc6b5d466bdc94ede1488e';
$apiKey = config('services.retell.api_key');

echo "=== Importiere fehlenden Call ===\n";
echo "Call ID: $callId\n\n";

// Hole Call Details
$ch = curl_init("https://api.retellai.com/v2/get-call/$callId");
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
    echo "✅ Call gefunden\n";
    
    // Simuliere Webhook
    $webhookData = [
        'event' => 'call_ended',
        'call_id' => $callData['call_id'],
        'to_number' => $callData['to_number'] ?? $callData['to'] ?? '+493083793369',
        'from_number' => $callData['from_number'] ?? $callData['from'] ?? null,
        'agent_id' => $callData['agent_id'] ?? null,
        'call_status' => $callData['call_status'] ?? 'ended',
        'start_timestamp' => $callData['start_timestamp'] ?? null,
        'end_timestamp' => $callData['end_timestamp'] ?? null,
        'duration' => $callData['end_timestamp'] && $callData['start_timestamp'] 
            ? round(($callData['end_timestamp'] - $callData['start_timestamp']) / 1000)
            : null,
        'transcript' => $callData['transcript'] ?? null,
        'disconnection_reason' => $callData['disconnection_reason'] ?? null
    ];
    
    // Verwende den Simple Controller
    $controller = new \App\Http\Controllers\Api\RetellWebhookSimpleController();
    $request = new \Illuminate\Http\Request();
    $request->merge($webhookData);
    
    $response = $controller->handle($request);
    
    if ($response->getStatusCode() == 200) {
        echo "✅ Call erfolgreich importiert!\n";
        $content = json_decode($response->getContent(), true);
        echo "Call ID: " . $content['call_id'] . "\n";
    } else {
        echo "❌ Fehler beim Import: " . $response->getContent() . "\n";
    }
    
} else {
    echo "❌ Call nicht gefunden: HTTP $httpCode\n";
    echo "Response: $response\n";
}