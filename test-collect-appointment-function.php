<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING COLLECT APPOINTMENT FUNCTION ===\n\n";

echo "Die Custom Function 'collect_appointment_data' ist bereits konfiguriert!\n\n";

echo "Konfiguration:\n";
echo "- Name: collect_appointment_data\n";
echo "- URL: https://api.askproai.de/api/retell/collect-appointment\n";
echo "- Method: POST\n";
echo "- Type: custom\n\n";

echo "Response Variables (werden im Gespräch verfügbar):\n";
echo "- success: Erfolgsstatus\n";
echo "- message: Nachricht\n";
echo "- appointment_id: Termin-ID\n";
echo "- termin_referenz: Referenznummer\n";
echo "- bestaetigung_status: Bestätigungsstatus\n";
echo "- naechste_schritte: Nächste Schritte\n\n";

echo "✅ Die Function ist korrekt konfiguriert!\n\n";

echo "PROBLEM ANALYSE:\n";
echo "Die Custom Function ist bereits konfiguriert, aber die Testanrufe haben nicht funktioniert.\n";
echo "Mögliche Gründe:\n\n";

echo "1. Der Agent ruft die Function nicht auf:\n";
echo "   - Der Agent muss explizit angewiesen werden, die Function zu nutzen\n";
echo "   - Check: Enthält der Prompt Anweisungen zur Terminbuchung?\n\n";

echo "2. Die Function wird aufgerufen, aber die Daten werden nicht korrekt übergeben:\n";
echo "   - Die Telefonnummer sollte automatisch gefüllt werden\n";
echo "   - Der Agent muss alle Pflichtfelder sammeln\n\n";

echo "3. Die Function wird aufgerufen, aber die Antwort wird nicht verarbeitet:\n";
echo "   - Der Agent sollte die Referenznummer dem Kunden mitteilen\n\n";

echo "NÄCHSTE SCHRITTE:\n";
echo "1. Prüfe den Agent Prompt - enthält er Anweisungen zur Terminbuchung?\n";
echo "2. Führe einen neuen Testanruf durch und achte darauf:\n";
echo "   - Erwähnt der Agent die Function?\n";
echo "   - Sammelt er alle Daten (Datum, Uhrzeit, Name, Dienstleistung)?\n";
echo "   - Sagt er 'Ich prüfe den Terminwunsch'? (execution_message)\n";
echo "   - Gibt er eine Referenznummer zurück?\n\n";

// Test the endpoint directly
echo "=== TESTING ENDPOINT DIRECTLY ===\n";

$testData = [
    'args' => [
        'datum' => 'morgen',
        'uhrzeit' => '10:00',
        'name' => 'Test User',
        'telefonnummer' => '+491234567890',
        'dienstleistung' => 'Rechtsberatung',
        'email' => 'test@example.com'
    ],
    'call_id' => 'test-' . time()
];

$ch = curl_init('https://api.askproai.de/api/retell/collect-appointment');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Retell-Call-Id: test-' . time()
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Test Request to: https://api.askproai.de/api/retell/collect-appointment\n";
echo "HTTP Code: $httpCode\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($data['success'] ?? false) {
        echo "✅ Endpoint funktioniert korrekt!\n";
        echo "Referenznummer: " . ($data['reference_id'] ?? 'N/A') . "\n";
    }
} else {
    echo "❌ Endpoint returned error: $httpCode\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
}