<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\CallActivity;

echo "=== Prüfung auf doppelte E-Mail-Versendungen ===\n\n";

// Prüfe Call 229 spezifisch
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);

if (!$call) {
    echo "Call 229 nicht gefunden.\n";
    exit(1);
}

// Hole alle E-Mail-Aktivitäten für diesen Call
$emailActivities = CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', 229)
    ->where('activity_type', 'email_sent')
    ->orderBy('created_at', 'desc')
    ->get();

echo "Call ID: 229\n";
echo "Anzahl E-Mail-Aktivitäten: " . $emailActivities->count() . "\n\n";

if ($emailActivities->count() > 0) {
    echo "E-Mail-Versand Historie:\n";
    echo str_repeat('-', 80) . "\n";
    
    foreach ($emailActivities as $index => $activity) {
        echo ($index + 1) . ". E-Mail-Aktivität:\n";
        echo "   ID: {$activity->id}\n";
        echo "   Zeitpunkt: {$activity->created_at->format('d.m.Y H:i:s')}\n";
        echo "   Titel: {$activity->title}\n";
        echo "   Beschreibung: {$activity->description}\n";
        
        if ($activity->metadata) {
            echo "   Metadaten:\n";
            if (isset($activity->metadata['recipients'])) {
                echo "     - Empfänger: " . implode(', ', $activity->metadata['recipients']) . "\n";
            }
            if (isset($activity->metadata['queued_at'])) {
                echo "     - In Queue gestellt: {$activity->metadata['queued_at']}\n";
            }
            if (isset($activity->metadata['sent_by'])) {
                echo "     - Gesendet von: {$activity->metadata['sent_by']}\n";
            }
        }
        
        echo "\n";
    }
}

// Prüfe aktuelle Jobs in der Queue
echo "\nAktuelle E-Mail-Jobs in der Queue:\n";
echo str_repeat('-', 80) . "\n";

$jobs = \DB::table('jobs')->get();
$mailJobs = 0;

foreach ($jobs as $job) {
    $payload = json_decode($job->payload, true);
    if (isset($payload['displayName']) && strpos($payload['displayName'], 'Mail') !== false) {
        $mailJobs++;
        echo "Job ID: {$job->id}\n";
        echo "Queue: {$job->queue}\n";
        echo "Attempts: {$job->attempts}\n";
        echo "Created: " . date('Y-m-d H:i:s', $job->created_at) . "\n\n";
    }
}

if ($mailJobs == 0) {
    echo "Keine E-Mail-Jobs in der Warteschlange.\n";
}

// Prüfe Failed Jobs
echo "\nFehlgeschlagene E-Mail-Jobs:\n";
echo str_repeat('-', 80) . "\n";

$failedJobs = \DB::table('failed_jobs')
    ->whereRaw("payload LIKE '%CallSummaryEmail%'")
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

if ($failedJobs->count() > 0) {
    foreach ($failedJobs as $job) {
        echo "Failed Job ID: {$job->id}\n";
        echo "Failed at: {$job->failed_at}\n";
        $exception = substr($job->exception, 0, 200);
        echo "Exception: {$exception}...\n\n";
    }
} else {
    echo "Keine fehlgeschlagenen E-Mail-Jobs gefunden.\n";
}

// Prüfe Horizon Metriken
echo "\nHorizon Queue Metriken:\n";
echo str_repeat('-', 80) . "\n";

try {
    $metrics = \Laravel\Horizon\Contracts\MetricsRepository::class;
    if (app()->bound($metrics)) {
        $repository = app($metrics);
        $throughput = $repository->throughputForQueue('default');
        echo "Queue Throughput (letzte Stunde): " . array_sum($throughput) . " Jobs\n";
    }
} catch (\Exception $e) {
    echo "Horizon Metriken nicht verfügbar.\n";
}

// Zusammenfassung
echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "1. E-Mail-Aktivitäten für Call 229: " . $emailActivities->count() . "\n";
echo "2. Aktuelle E-Mail-Jobs in Queue: {$mailJobs}\n";
echo "3. Fehlgeschlagene E-Mail-Jobs: " . $failedJobs->count() . "\n";

if ($emailActivities->count() > 1) {
    echo "\n⚠️  WARNUNG: Es wurden mehrere E-Mail-Aktivitäten für diesen Call gefunden!\n";
    echo "Das könnte auf doppelte E-Mail-Versendungen hindeuten.\n";
}