<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\CallActivity;

echo "=== Test der E-Mail-Duplikatsprüfung ===\n\n";

// Prüfe Call 229
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);

if (!$call) {
    echo "Call 229 nicht gefunden.\n";
    exit(1);
}

// Simuliere E-Mail-Versand Request
$controller = new \App\Http\Controllers\Portal\Api\CallApiController();
$request = new \Illuminate\Http\Request();
$request->setMethod('POST');
$request->merge([
    'recipients' => ['fabianspitzer@icloud.com'],
    'include_transcript' => true,
    'message' => 'Test-Nachricht für Duplikatsprüfung'
]);

// Mock Auth
$user = \App\Models\PortalUser::first();
\Auth::guard('portal')->setUser($user);

// Set company context
app()->instance('current_company_id', $call->company_id);

echo "Test 1: Versuche E-Mail an fabianspitzer@icloud.com zu senden (sollte blockiert werden)...\n";

try {
    $response = $controller->sendSummary($request, $call);
    $data = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 400) {
        echo "✅ E-Mail wurde korrekt blockiert!\n";
        echo "Meldung: " . $data['message'] . "\n";
        if (isset($data['duplicate_recipients'])) {
            echo "Duplikat-Empfänger: " . implode(', ', $data['duplicate_recipients']) . "\n";
        }
    } else {
        echo "❌ E-Mail wurde nicht blockiert (Status: " . $response->getStatusCode() . ")\n";
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}

echo "\nTest 2: Versuche E-Mail an neue Adresse zu senden (sollte funktionieren)...\n";

$request->merge([
    'recipients' => ['test-' . time() . '@askproai.de'],
    'include_transcript' => true,
    'message' => 'Test-Nachricht für neue E-Mail'
]);

try {
    $response = $controller->sendSummary($request, $call);
    $data = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 200) {
        echo "✅ E-Mail wurde erfolgreich in die Queue gestellt!\n";
        echo "Meldung: " . $data['message'] . "\n";
    } else {
        echo "❌ Unerwartete Response (Status: " . $response->getStatusCode() . ")\n";
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}

echo "\nTest 3: Versuche gemischte Empfänger (alt + neu)...\n";

$request->merge([
    'recipients' => ['fabianspitzer@icloud.com', 'new-' . time() . '@askproai.de'],
    'include_transcript' => true,
    'message' => 'Test-Nachricht für gemischte Empfänger'
]);

try {
    $response = $controller->sendSummary($request, $call);
    $data = json_decode($response->getContent(), true);
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    
    if (isset($data['duplicate_recipients']) && count($data['duplicate_recipients']) > 0) {
        echo "✅ Duplikate wurden erkannt und übersprungen\n";
    }
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "Die Duplikatsprüfung prüft E-Mails der letzten 5 Minuten.\n";
echo "Bereits versendete E-Mails werden automatisch übersprungen.\n";
echo "Neue Empfänger erhalten die E-Mail normal.\n";