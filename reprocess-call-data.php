#!/usr/bin/env php
<?php
/**
 * Reprocess Call Data Script
 * 
 * Dieses Script verarbeitet alle bestehenden Calls neu, um fehlende Daten
 * aus dem raw_data zu extrahieren (z.B. summary, datum_termin, etc.)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Helpers\RetellDataExtractor;
use Illuminate\Support\Facades\DB;

echo "=== Reprocess Call Data Script ===\n";
echo "Dieses Script extrahiert fehlende Daten aus bestehenden Calls.\n\n";

// Statistiken
$stats = [
    'total' => 0,
    'updated' => 0,
    'summary_added' => 0,
    'appointment_data_added' => 0,
    'skipped' => 0,
    'errors' => 0
];

try {
    // Hole alle Calls mit raw_data
    $calls = Call::whereNotNull('raw_data')
        ->orderBy('created_at', 'desc')
        ->get();
    
    $stats['total'] = $calls->count();
    
    echo "Gefundene Calls mit raw_data: {$stats['total']}\n\n";
    
    foreach ($calls as $call) {
        echo "Verarbeite Call ID: {$call->id} (Retell ID: {$call->retell_call_id})... ";
        
        try {
            $rawData = is_string($call->raw_data) ? json_decode($call->raw_data, true) : $call->raw_data;
            
            if (!$rawData) {
                echo "ÜBERSPRUNGEN (keine raw_data)\n";
                $stats['skipped']++;
                continue;
            }
            
            // Extrahiere Daten neu
            $extractedData = RetellDataExtractor::extractUpdateData($rawData);
            
            $updates = [];
            $updateReasons = [];
            
            // Prüfe und sammle Updates
            if (empty($call->summary) && !empty($extractedData['summary'])) {
                $updates['summary'] = $extractedData['summary'];
                $updates['call_summary'] = $extractedData['summary'];
                $updateReasons[] = 'Summary';
                $stats['summary_added']++;
            }
            
            if (empty($call->datum_termin) && !empty($extractedData['datum_termin'])) {
                $updates['datum_termin'] = $extractedData['datum_termin'];
                $updateReasons[] = 'Datum';
                $stats['appointment_data_added']++;
            }
            
            if (empty($call->uhrzeit_termin) && !empty($extractedData['uhrzeit_termin'])) {
                $updates['uhrzeit_termin'] = $extractedData['uhrzeit_termin'];
                $updateReasons[] = 'Uhrzeit';
            }
            
            if (empty($call->dienstleistung) && !empty($extractedData['dienstleistung'])) {
                $updates['dienstleistung'] = $extractedData['dienstleistung'];
                $updateReasons[] = 'Dienstleistung';
            }
            
            if (empty($call->extracted_name) && !empty($extractedData['extracted_name'])) {
                $updates['extracted_name'] = $extractedData['extracted_name'];
                $updateReasons[] = 'Name';
            }
            
            if (empty($call->extracted_email) && !empty($extractedData['extracted_email'])) {
                $updates['extracted_email'] = $extractedData['extracted_email'];
                $updateReasons[] = 'Email';
            }
            
            if (empty($call->extracted_phone) && !empty($extractedData['extracted_phone'])) {
                $updates['extracted_phone'] = $extractedData['extracted_phone'];
                $updateReasons[] = 'Telefon';
            }
            
            if (empty($call->reason_for_visit) && !empty($extractedData['reason_for_visit'])) {
                $updates['reason_for_visit'] = $extractedData['reason_for_visit'];
                $updateReasons[] = 'Grund';
            }
            
            // Führe Update durch wenn nötig
            if (!empty($updates)) {
                $call->update($updates);
                $stats['updated']++;
                echo "AKTUALISIERT (" . implode(', ', $updateReasons) . ")\n";
            } else {
                echo "KEINE ÄNDERUNGEN\n";
            }
            
        } catch (\Exception $e) {
            echo "FEHLER: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
    }
    
    echo "\n=== Zusammenfassung ===\n";
    echo "Gesamt verarbeitet: {$stats['total']}\n";
    echo "Aktualisiert: {$stats['updated']}\n";
    echo "Summary hinzugefügt: {$stats['summary_added']}\n";
    echo "Termindaten hinzugefügt: {$stats['appointment_data_added']}\n";
    echo "Übersprungen: {$stats['skipped']}\n";
    echo "Fehler: {$stats['errors']}\n";
    
    if ($stats['updated'] > 0) {
        echo "\n✅ Daten erfolgreich aktualisiert!\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ FEHLER: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✨ Script beendet.\n";