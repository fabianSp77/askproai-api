<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RetellWebhookController;
use Illuminate\Http\Request;

echo "=== Direkter Controller Test ===\n\n";

// Erstelle Controller-Instanz
$controller = new RetellWebhookController();

// Simuliere Request
$data = [
    'call_id' => 'direct_test_' . time(),
    'phone_number' => '+491234567890',
    '_datum__termin' => '2025-06-15',
    '_uhrzeit__termin' => '10:00',
    '_dienstleistung' => 'Herren: Waschen, Schneiden, Styling',
    '_name' => 'Direkter Test',
    '_email' => 'test@example.com',
    'transcript' => 'Test Transkript',
    'user_sentiment' => 'positive',
    'call_successful' => true,
    'duration' => 120
];

$request = Request::create('/api/webhooks/retell', 'POST', $data);
$request->headers->set('Content-Type', 'application/json');

try {
    $response = $controller->handleWebhook($request);
    echo "✅ Controller-Aufruf erfolgreich!\n";
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $response->getContent() . "\n";
    
    // Prüfe Datenbank
    echo "\n=== Prüfe Datenbank ===\n";
    
    use App\Models\Call;
    $call = Call::where('call_id', $data['call_id'])->first();
    
    if ($call) {
        echo "✅ Call wurde gespeichert!\n";
        echo "ID: " . $call->id . "\n";
        echo "Name: " . $call->name . "\n";
        echo "Email: " . $call->email . "\n";
    } else {
        echo "❌ Call wurde nicht in der Datenbank gefunden\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
