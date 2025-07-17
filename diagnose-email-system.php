<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DIAGNOSE E-Mail System ===\n\n";

// 1. Test basic mail configuration
echo "1. Mail Konfiguration:\n";
echo "   Driver: " . config('mail.default') . "\n";
echo "   From: " . config('mail.from.address') . "\n";
echo "   From Name: " . config('mail.from.name') . "\n";
echo "   Resend Key exists: " . (config('services.resend.key') ? 'YES' : 'NO') . "\n\n";

// 2. Check Resend Transport
echo "2. Resend Transport Check:\n";
try {
    $transport = \Illuminate\Support\Facades\Mail::getSymfonyTransport();
    echo "   Transport Class: " . get_class($transport) . "\n";
    echo "   Is ResendTransport: " . ($transport instanceof \App\Mail\Transport\ResendTransport ? 'YES' : 'NO') . "\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// 3. Test Resend API directly
echo "3. Resend API Direct Test:\n";
$apiKey = config('services.resend.key');
$testId = uniqid('test-');

$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'from' => 'info@askproai.de',
    'to' => 'fabianspitzer@icloud.com',
    'subject' => 'Diagnose Test - ' . $testId,
    'html' => '<h1>Diagnose Test</h1><p>Test ID: ' . $testId . '</p><p>Zeit: ' . now()->format('d.m.Y H:i:s') . '</p>'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   Response: $response\n";
if ($error) {
    echo "   cURL Error: $error\n";
}
echo "\n";

// 4. Check Failed Jobs
echo "4. Failed Jobs (letzte 24h):\n";
$failedJobs = \DB::table('failed_jobs')
    ->where('failed_at', '>', now()->subDay())
    ->orderBy('failed_at', 'desc')
    ->limit(10)
    ->get();

if ($failedJobs->count() > 0) {
    foreach ($failedJobs as $job) {
        $payload = json_decode($job->payload, true);
        echo "   - " . $job->failed_at . ": " . ($payload['displayName'] ?? 'Unknown') . "\n";
        if (str_contains($job->exception, 'Mail') || str_contains($job->exception, 'Resend')) {
            echo "     Exception: " . substr($job->exception, 0, 200) . "...\n";
        }
    }
} else {
    echo "   ✅ Keine fehlgeschlagenen Jobs\n";
}
echo "\n";

// 5. Check if mails are stuck in queue
echo "5. E-Mails in Queue:\n";
$redis = app('redis');
$queues = ['default', 'high', 'emails', 'low'];
$mailJobsFound = 0;

foreach ($queues as $queue) {
    $jobs = $redis->lrange("queues:{$queue}", 0, -1);
    foreach ($jobs as $jobData) {
        $job = json_decode($jobData, true);
        if (isset($job['displayName']) && str_contains($job['displayName'], 'Mail')) {
            $mailJobsFound++;
            echo "   - Queue '$queue': " . $job['displayName'] . " (pushed: " . date('Y-m-d H:i:s', $job['pushedAt']) . ")\n";
        }
    }
}
if ($mailJobsFound == 0) {
    echo "   ✅ Keine E-Mail Jobs in Queue\n";
}
echo "\n";

// 6. Test simple mail send
echo "6. Einfacher Mail-Test:\n";
try {
    $message = (new \Symfony\Component\Mime\Email())
        ->from(new \Symfony\Component\Mime\Address('info@askproai.de', 'AskProAI'))
        ->to('fabianspitzer@icloud.com')
        ->subject('Symfony Direct Test - ' . now()->format('H:i:s'))
        ->html('<p>Dies ist ein direkter Test mit Symfony Mailer.</p>');
    
    $transport = new \App\Mail\Transport\ResendTransport($apiKey);
    $transport->send(\Symfony\Component\Mailer\SentMessage::create($message, \Symfony\Component\Mailer\Envelope::create($message)));
    
    echo "   ✅ Symfony Mailer Test erfolgreich\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
if ($httpCode == 200) {
    echo "✅ Resend API funktioniert\n";
    echo "✅ E-Mail wurde an Resend übermittelt\n";
    echo "\nMögliche Probleme:\n";
    echo "- E-Mails landen im Spam\n";
    echo "- Resend Queue ist überlastet\n";
    echo "- Domain/SPF Probleme\n\n";
    echo "Prüfen Sie:\n";
    echo "1. Resend Dashboard: https://resend.com/emails\n";
    echo "2. Spam-Ordner bei fabianspitzer@icloud.com\n";
} else {
    echo "❌ Problem mit Resend API\n";
}