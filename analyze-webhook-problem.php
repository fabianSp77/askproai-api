<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;

echo "=== WEBHOOK PROBLEM ANALYSE ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Überprüfe was mit call_started passiert
echo "1. TEST: call_started Event\n";
echo str_repeat("-", 40) . "\n";

// Simuliere call_started Event
$testData = [
    'event' => 'call_started',
    'call_id' => 'test_started_' . time(),
    'to_number' => '+493083793369',
    'from_number' => '+491234567890',
    'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
    'call_status' => 'in_progress',
    'start_timestamp' => time() * 1000
];

$controller = new \App\Http\Controllers\Api\RetellWebhookSimpleController();
$request = new \Illuminate\Http\Request();
$request->merge($testData);

try {
    $response = $controller->handle($request);
    $content = json_decode($response->getContent(), true);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response: " . json_encode($content) . "\n";
    
    if ($response->getStatusCode() !== 200) {
        echo "❌ call_started wird nicht korrekt verarbeitet!\n";
    } else {
        echo "✅ call_started wird verarbeitet\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

// 2. Überprüfe die Event-Filter
echo "\n2. EVENT FILTER CHECK:\n";
echo str_repeat("-", 40) . "\n";

$controllerCode = file_get_contents('/var/www/api-gateway/app/Http/Controllers/Api/RetellWebhookSimpleController.php');

// Suche nach Event-Filter
if (preg_match('/if\s*\(!in_array\(\$event,\s*\[(.*?)\]\)\)/', $controllerCode, $matches)) {
    echo "Erlaubte Events: " . $matches[1] . "\n";
    
    if (strpos($matches[1], 'call_started') === false) {
        echo "❌ call_started ist NICHT in der erlaubten Event-Liste!\n";
        echo "   Das ist das Problem!\n";
    }
}

// 3. Fix vorschlagen
echo "\n3. LÖSUNG:\n";
echo str_repeat("-", 40) . "\n";
echo "Der Webhook Controller filtert call_started Events heraus!\n";
echo "Er erlaubt nur: ['call_ended', 'call_analyzed']\n";
echo "\nDas bedeutet:\n";
echo "- Live-Anzeige während des Anrufs funktioniert nicht\n";
echo "- Anrufe werden erst nach Beendigung angezeigt\n";

// 4. Weitere Tests
echo "\n4. WEITERE PROBLEME:\n";
echo str_repeat("-", 40) . "\n";

// Prüfe ob es doppelte Webhook-Registrierungen gibt
$webhookRoutes = shell_exec("grep -n 'retell/webhook' /var/www/api-gateway/routes/api.php");
echo "Webhook Routes:\n$webhookRoutes\n";

// Failed Jobs analysieren
$failedJobs = \Illuminate\Support\Facades\Redis::zrevrange('askproaifailed_jobs', 0, -1);
echo "\nFailed Jobs: " . count($failedJobs) . "\n";
foreach ($failedJobs as $jobId) {
    $jobData = \Illuminate\Support\Facades\Redis::hgetall($jobId);
    if (isset($jobData['name'])) {
        echo "- " . $jobData['name'] . "\n";
        if (isset($jobData['exception']) && preg_match('/"message":"([^"]+)"/', $jobData['exception'], $matches)) {
            echo "  Error: " . $matches[1] . "\n";
        }
    }
}