<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Redis;

echo "=== Analyse aller Failed Jobs ===\n\n";

// Hole alle failed job IDs
$failedJobIds = Redis::zrange('askproaifailed_jobs', 0, -1);
echo "Gefundene Failed Jobs: " . count($failedJobIds) . "\n\n";

$jobTypes = [];
$errorTypes = [];
$dates = [];

foreach ($failedJobIds as $index => $jobId) {
    $data = Redis::hgetall("askproai$jobId");
    
    if ($data && isset($data['payload'])) {
        $payload = json_decode($data['payload'], true);
        $jobName = $payload['displayName'] ?? 'Unknown';
        $failedAt = isset($data['failed_at']) ? date('Y-m-d H:i:s', $data['failed_at'] / 1000) : 'Unknown';
        
        // Zähle Job-Typen
        if (!isset($jobTypes[$jobName])) {
            $jobTypes[$jobName] = 0;
        }
        $jobTypes[$jobName]++;
        
        // Datum
        $date = date('Y-m-d', isset($data['failed_at']) ? $data['failed_at'] / 1000 : time());
        if (!isset($dates[$date])) {
            $dates[$date] = 0;
        }
        $dates[$date]++;
        
        // Exception Details
        if (isset($data['exception'])) {
            // Extrahiere Fehlertyp
            if (preg_match('/Exception: (.+?) in/', $data['exception'], $matches)) {
                $errorMsg = $matches[1];
                if (!isset($errorTypes[$errorMsg])) {
                    $errorTypes[$errorMsg] = 0;
                }
                $errorTypes[$errorMsg]++;
            }
        }
        
        // Zeige erste 5 Details
        if ($index < 5) {
            echo "Job #" . ($index + 1) . "\n";
            echo "ID: $jobId\n";
            echo "Type: $jobName\n";
            echo "Failed At: $failedAt\n";
            
            if (isset($data['exception'])) {
                $lines = explode("\n", $data['exception']);
                echo "Error: " . $lines[0] . "\n";
            }
            
            // Bei ProcessRetellCallEndedJob zeige Call-ID
            if ($jobName === 'App\\Jobs\\ProcessRetellCallEndedJob' && isset($payload['data'])) {
                $jobData = json_decode($payload['data']['command'], true);
                if ($jobData && isset($jobData['data']['call']['call_id'])) {
                    echo "Call ID: " . $jobData['data']['call']['call_id'] . "\n";
                }
            }
            
            echo "------------------------\n\n";
        }
    }
}

echo "\n=== Zusammenfassung ===\n";
echo "\nJob-Typen:\n";
arsort($jobTypes);
foreach ($jobTypes as $type => $count) {
    echo "- $type: $count\n";
}

echo "\nFehler-Typen:\n";
arsort($errorTypes);
foreach ($errorTypes as $error => $count) {
    echo "- " . substr($error, 0, 80) . "...: $count\n";
}

echo "\nNach Datum:\n";
krsort($dates);
foreach ($dates as $date => $count) {
    echo "- $date: $count Jobs\n";
}

// Prüfe ob es aktuelle Failed Jobs gibt (heute)
$today = date('Y-m-d');
if (isset($dates[$today])) {
    echo "\n⚠️ WARNUNG: " . $dates[$today] . " Jobs sind HEUTE fehlgeschlagen!\n";
}