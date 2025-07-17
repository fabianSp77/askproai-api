#!/usr/bin/env php
<?php
/**
 * Generate Call Summaries Script
 * 
 * Dieses Script generiert Zusammenfassungen für Calls die Transkripte haben
 * aber noch keine Summary.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use Illuminate\Support\Facades\DB;

echo "=== Generate Call Summaries Script ===\n";
echo "Dieses Script generiert Zusammenfassungen aus Transkripten.\n\n";

// Statistiken
$stats = [
    'total' => 0,
    'processed' => 0,
    'skipped' => 0,
    'errors' => 0
];

try {
    // Hole alle Calls mit Transkript aber ohne Summary
    $calls = Call::whereNotNull('transcript')
        ->where('transcript', '!=', '')
        ->whereNull('summary')
        ->orderBy('created_at', 'desc')
        ->get();
    
    $stats['total'] = $calls->count();
    
    echo "Gefundene Calls mit Transkript aber ohne Summary: {$stats['total']}\n\n";
    
    foreach ($calls as $call) {
        echo "Verarbeite Call ID: {$call->id}... ";
        
        try {
            // Prüfe ob custom_analysis_data eine Summary enthält
            $customData = is_string($call->custom_analysis_data) 
                ? json_decode($call->custom_analysis_data, true) 
                : $call->custom_analysis_data;
            
            $summary = null;
            
            // Versuche Summary aus verschiedenen Quellen zu extrahieren
            if (isset($customData['summary'])) {
                $summary = $customData['summary'];
            } elseif (isset($customData['call_summary'])) {
                $summary = $customData['call_summary'];
            } else {
                // Generiere eine einfache Summary aus dem Transkript
                $summary = generateSimpleSummary($call);
            }
            
            if ($summary) {
                $call->update([
                    'summary' => $summary,
                    'call_summary' => $summary
                ]);
                $stats['processed']++;
                echo "SUMMARY GENERIERT\n";
            } else {
                $stats['skipped']++;
                echo "ÜBERSPRUNGEN (keine Summary möglich)\n";
            }
            
        } catch (\Exception $e) {
            echo "FEHLER: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
    }
    
    echo "\n=== Zusammenfassung ===\n";
    echo "Gesamt gefunden: {$stats['total']}\n";
    echo "Verarbeitet: {$stats['processed']}\n";
    echo "Übersprungen: {$stats['skipped']}\n";
    echo "Fehler: {$stats['errors']}\n";
    
} catch (\Exception $e) {
    echo "\n❌ FEHLER: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

/**
 * Generiere eine einfache Summary aus dem Transkript und anderen Daten
 */
function generateSimpleSummary($call) {
    $summary = [];
    
    // Extrahiere Kundenname
    $customerName = $call->extracted_name;
    if (!$customerName && $call->customer_data_backup) {
        $backup = is_string($call->customer_data_backup) 
            ? json_decode($call->customer_data_backup, true) 
            : $call->customer_data_backup;
        $customerName = $backup['full_name'] ?? null;
    }
    
    if ($customerName) {
        $summary[] = "Anruf von $customerName";
    } else {
        $summary[] = "Anruf von " . $call->from_number;
    }
    
    // Extrahiere Anfrage
    $request = null;
    if ($call->custom_analysis_data) {
        $custom = is_string($call->custom_analysis_data) 
            ? json_decode($call->custom_analysis_data, true) 
            : $call->custom_analysis_data;
        $request = $custom['customer_request'] ?? null;
    }
    
    if (!$request && $call->customer_data_backup) {
        $backup = is_string($call->customer_data_backup) 
            ? json_decode($call->customer_data_backup, true) 
            : $call->customer_data_backup;
        $request = $backup['request'] ?? null;
    }
    
    if ($request) {
        $summary[] = "Anfrage: $request";
    }
    
    // Termin-Status
    if ($call->appointment_requested) {
        $summary[] = "Termin wurde angefragt";
        if ($call->datum_termin) {
            $summary[] = "für den " . $call->datum_termin;
            if ($call->uhrzeit_termin) {
                $summary[] = "um " . $call->uhrzeit_termin;
            }
        }
    }
    
    // Dienstleistung
    if ($call->dienstleistung) {
        $summary[] = "Dienstleistung: " . $call->dienstleistung;
    }
    
    // Dauer
    if ($call->duration_sec > 0) {
        $minutes = round($call->duration_sec / 60, 1);
        $summary[] = "Gesprächsdauer: {$minutes} Minuten";
    }
    
    return implode('. ', $summary) . '.';
}

echo "\n✨ Script beendet.\n";