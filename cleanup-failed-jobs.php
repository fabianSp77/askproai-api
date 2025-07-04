<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Redis;

echo "=== Bereinigung Failed Jobs ===\n\n";

// Hole alle failed job IDs
$failedJobIds = Redis::zrange('askproaifailed_jobs', 0, -1);
echo "Gefundene Failed Jobs: " . count($failedJobIds) . "\n\n";

$answer = readline("Sollen alle Failed Jobs gelöscht werden? (yes/no): ");

if (strtolower($answer) !== 'yes') {
    echo "Abgebrochen.\n";
    exit;
}

$deleted = 0;

foreach ($failedJobIds as $jobId) {
    // Lösche Job-Daten
    Redis::del("askproai$jobId");
    
    // Entferne aus failed_jobs Set
    Redis::zrem('askproaifailed_jobs', $jobId);
    Redis::zrem('askproairecent_failed_jobs', $jobId);
    
    $deleted++;
    
    if ($deleted % 10 == 0) {
        echo "Gelöscht: $deleted Jobs...\n";
    }
}

echo "\n✅ Erfolgreich $deleted Failed Jobs gelöscht!\n";

// Prüfe ob noch Jobs vorhanden
$remaining = Redis::zcard('askproaifailed_jobs');
echo "Verbleibende Failed Jobs: $remaining\n";