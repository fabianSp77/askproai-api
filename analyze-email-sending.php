<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CallActivity;

echo "=== Analyse der E-Mail-Versendungen ===\n\n";

// Hole alle E-Mail-Aktivitäten
$emailActivities = CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('activity_type', 'email_sent')
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

echo "Letzte 20 E-Mail-Aktivitäten:\n";
echo str_repeat('=', 100) . "\n";
echo sprintf("%-5s | %-10s | %-25s | %-40s | %-10s\n", "ID", "Call ID", "Zeitpunkt", "Empfänger", "Status");
echo str_repeat('-', 100) . "\n";

foreach ($emailActivities as $activity) {
    $recipients = isset($activity->metadata['recipients']) ? implode(', ', $activity->metadata['recipients']) : 'N/A';
    $status = strpos($activity->title, 'Warteschlange') !== false ? 'Queued' : 'Sent';
    
    echo sprintf(
        "%-5s | %-10s | %-25s | %-40s | %-10s\n",
        $activity->id,
        $activity->call_id,
        $activity->created_at->format('d.m.Y H:i:s'),
        substr($recipients, 0, 40),
        $status
    );
}

// Prüfe Log-Dateien auf tatsächliche E-Mail-Versendungen
echo "\n\nPrüfe Laravel-Logs auf E-Mail-Versendungen (letzte 24 Stunden):\n";
echo str_repeat('=', 100) . "\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $yesterday = now()->subDay()->timestamp;
    $lines = file($logFile);
    $mailEvents = [];
    
    foreach ($lines as $i => $line) {
        // Suche nach E-Mail-bezogenen Log-Einträgen
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*mail\.sending|mail\.sent|CallSummaryEmail/i', $line, $matches)) {
            $timestamp = strtotime($matches[1]);
            if ($timestamp > $yesterday) {
                $mailEvents[] = [
                    'time' => $matches[1],
                    'line' => trim($line)
                ];
            }
        }
    }
    
    if (count($mailEvents) > 0) {
        echo "Gefundene E-Mail-Events:\n";
        foreach ($mailEvents as $event) {
            echo "{$event['time']}: " . substr($event['line'], 0, 150) . "...\n";
        }
    } else {
        echo "Keine E-Mail-Events in den letzten 24 Stunden gefunden.\n";
    }
}

// Prüfe, ob es Konfigurationsprobleme gibt
echo "\n\nAktuelle E-Mail-Konfiguration:\n";
echo str_repeat('=', 100) . "\n";
echo "Mail Driver: " . config('mail.default') . "\n";
echo "Queue Connection: " . config('queue.default') . "\n";
echo "From Address: " . config('mail.from.address') . "\n";

// Empfehlung
echo "\n\n=== EMPFEHLUNG ===\n";
echo "Um doppelte E-Mail-Versendungen zu verhindern:\n";
echo "1. Verwenden Sie eindeutige Job-IDs oder Idempotenz-Keys\n";
echo "2. Implementieren Sie eine Duplicate-Check vor dem Versand\n";
echo "3. Nutzen Sie die Queue-Retry-Logik richtig (--tries=1)\n";

// Check für Call 229 spezifisch
echo "\n\nFür Call 229 spezifisch:\n";
$call229Activities = CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', 229)
    ->where('activity_type', 'email_sent')
    ->orderBy('created_at', 'asc')
    ->get();

$uniqueRecipients = [];
foreach ($call229Activities as $activity) {
    if (isset($activity->metadata['recipients'])) {
        foreach ($activity->metadata['recipients'] as $recipient) {
            if (!isset($uniqueRecipients[$recipient])) {
                $uniqueRecipients[$recipient] = 0;
            }
            $uniqueRecipients[$recipient]++;
        }
    }
}

echo "Empfänger-Analyse:\n";
foreach ($uniqueRecipients as $email => $count) {
    echo "- {$email}: {$count}x E-Mail-Aktivitäten\n";
    if ($count > 1) {
        echo "  ⚠️  Dieser Empfänger hat mehrere E-Mail-Aktivitäten!\n";
    }
}