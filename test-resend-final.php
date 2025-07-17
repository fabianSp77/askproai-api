<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FINALER RESEND TEST ===\n\n";

// 1. Direkt mit Resend API
echo "1. DIREKTER RESEND API TEST:\n";
$apiKey = config('mail.mailers.resend.key');
echo "   API Key: " . substr($apiKey, 0, 15) . "...\n";

$payload = [
    'from' => 'AskProAI <info@askproai.de>',
    'to' => ['fabianspitzer@icloud.com'],
    'subject' => 'FINALER TEST - ' . now()->format('H:i:s'),
    'html' => '<h1>Test Email</h1><p>Diese Email wurde direkt über die Resend API gesendet.</p><p>Zeit: ' . now() . '</p>'
];

$ch = curl_init('https://api.resend.com/emails');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_VERBOSE => true,
    CURLOPT_STDERR => fopen('php://output', 'w'),
]);

$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "\n   Status: $statusCode\n";
echo "   Response: $response\n";
if ($error) {
    echo "   Error: $error\n";
}

// 2. Check Resend domain status
echo "\n2. PRÜFE RESEND DOMAIN STATUS:\n";
$ch = curl_init('https://api.resend.com/domains');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
    ],
]);

$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($statusCode === 200) {
    $domains = json_decode($response, true);
    if (isset($domains['data'])) {
        foreach ($domains['data'] as $domain) {
            echo "   Domain: " . $domain['name'] . " - Status: " . $domain['status'] . "\n";
            if ($domain['name'] === 'askproai.de') {
                echo "   ✅ askproai.de gefunden\n";
                echo "   Records: " . json_encode($domain['records'] ?? []) . "\n";
            }
        }
    }
}

// 3. Test mit Laravel Mail
echo "\n3. TEST MIT LARAVEL MAIL:\n";
try {
    // Force error logging
    config(['logging.channels.daily.level' => 'debug']);
    
    $call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(258);
    app()->instance('current_company_id', $call->company_id);
    
    $email = new \App\Mail\CallSummaryEmail(
        $call,
        true,
        true,
        'Laravel Mail Test - ' . now()->format('H:i:s'),
        'internal'
    );
    
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send($email);
    echo "   ✅ Laravel Mail completed\n";
    
    // Check for failures
    if (method_exists(\Illuminate\Support\Facades\Mail::class, 'failures')) {
        $failures = \Illuminate\Support\Facades\Mail::failures();
        if (!empty($failures)) {
            echo "   Failed: " . implode(', ', $failures) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
}

// 4. Check Horizon job processing
echo "\n4. HORIZON JOB TEST:\n";
$redis = app('redis');

// Clear any old jobs
$redis->del('queues:emails');

// Dispatch new job
\App\Jobs\SendCallSummaryEmailJob::dispatch(
    258,
    ['fabianspitzer@icloud.com'],
    true,
    true,
    'Horizon Job Test - ' . now()->format('H:i:s'),
    'internal'
);

$count = $redis->llen('queues:emails');
echo "   Jobs in queue: $count\n";

// Wait for processing
echo "   Waiting 5 seconds...\n";
sleep(5);

$finalCount = $redis->llen('queues:emails');
echo "   Jobs after wait: $finalCount\n";

if ($finalCount < $count) {
    echo "   ✅ Job was processed by Horizon\n";
} else {
    echo "   ❌ Job still in queue\n";
}

echo "\n=== EMAILS SOLLTEN JETZT ANKOMMEN! ===\n";
echo "Prüfe dein Postfach: fabianspitzer@icloud.com\n";