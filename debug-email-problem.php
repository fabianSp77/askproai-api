<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Mail\CallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "=== DEBUG E-Mail Problem für Call 228 ===\n\n";

// 1. Check Call
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(228);
if (!$call) {
    echo "❌ Call 228 nicht gefunden!\n";
    exit(1);
}

echo "1. Call Info:\n";
echo "   ID: {$call->id}\n";
echo "   Company: {$call->company->name}\n";
echo "   Created: {$call->created_at}\n\n";

// 2. Check Mail Configuration
echo "2. Mail Configuration:\n";
echo "   Driver: " . config('mail.default') . "\n";
echo "   From: " . config('mail.from.address') . "\n";
echo "   Resend Key: " . (config('services.resend.key') ? substr(config('services.resend.key'), 0, 10) . '...' : 'NOT SET') . "\n\n";

// 3. Check Recent Activities
echo "3. Letzte E-Mail Aktivitäten für Call 228:\n";
$activities = \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', 228)
    ->where('activity_type', 'email_sent')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($activities as $activity) {
    echo "   - {$activity->created_at->format('d.m.Y H:i:s')}: {$activity->description}\n";
    if (isset($activity->metadata['recipients'])) {
        echo "     Empfänger: " . implode(', ', $activity->metadata['recipients']) . "\n";
    }
}

// 4. Check Failed Jobs
echo "\n4. Failed Jobs prüfen:\n";
$failedCount = \DB::table('failed_jobs')->count();
echo "   Total Failed Jobs: $failedCount\n";

$recentFailed = \DB::table('failed_jobs')
    ->orderBy('failed_at', 'desc')
    ->limit(5)
    ->get();

foreach ($recentFailed as $job) {
    $payload = json_decode($job->payload, true);
    if (isset($payload['displayName']) && str_contains($payload['displayName'], 'Mail')) {
        echo "   - Failed: {$job->failed_at} - " . substr($job->exception, 0, 100) . "...\n";
    }
}

// 5. Test Direct Mail Send
echo "\n5. Test DIREKTER E-Mail-Versand (ohne Queue):\n";
try {
    Mail::to('fabianspitzer@icloud.com')->send(new CallSummaryEmail(
        $call,
        true,
        true,
        'DIRECT TEST - ' . now()->format('H:i:s'),
        'internal'
    ));
    echo "   ✅ Direkt-Versand erfolgreich!\n";
} catch (\Exception $e) {
    echo "   ❌ Direkt-Versand FEHLER: " . $e->getMessage() . "\n";
    echo "   Stack: " . $e->getTraceAsString() . "\n";
}

// 6. Test Queue Processing
echo "\n6. Test Queue-Versand:\n";
try {
    Mail::to('fabianspitzer@icloud.com')->queue(new CallSummaryEmail(
        $call,
        true,
        true,
        'QUEUE TEST - ' . now()->format('H:i:s'),
        'internal'
    ));
    echo "   ✅ In Queue gestellt\n";
    
    // Force process
    echo "   Verarbeite Queue manuell...\n";
    $exitCode = \Illuminate\Support\Facades\Artisan::call('queue:work', [
        '--stop-when-empty' => true,
        '--tries' => 1,
        '--queue' => 'default,emails'
    ]);
    
    $output = \Illuminate\Support\Facades\Artisan::output();
    echo "   Queue Output: " . trim($output) . "\n";
    
} catch (\Exception $e) {
    echo "   ❌ Queue-Versand FEHLER: " . $e->getMessage() . "\n";
}

// 7. Check Logs
echo "\n7. Letzte Log-Einträge:\n";
$logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
if (file_exists($logFile)) {
    $lines = [];
    $handle = fopen($logFile, 'r');
    if ($handle) {
        while (!feof($handle)) {
            $line = fgets($handle);
            if (stripos($line, 'mail') !== false || stripos($line, 'resend') !== false) {
                $lines[] = trim($line);
            }
        }
        fclose($handle);
    }
    
    $recentLogs = array_slice($lines, -5);
    foreach ($recentLogs as $log) {
        echo "   " . substr($log, 0, 150) . "...\n";
    }
}

// 8. Test Resend API directly
echo "\n8. Test Resend API direkt:\n";
$apiKey = config('services.resend.key');
if ($apiKey) {
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
        'subject' => 'Resend API Test - ' . now()->format('H:i:s'),
        'text' => 'Dies ist ein direkter Test der Resend API.',
        'html' => '<p>Dies ist ein <strong>direkter Test</strong> der Resend API.</p>'
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   HTTP Code: $httpCode\n";
    echo "   Response: $response\n";
}