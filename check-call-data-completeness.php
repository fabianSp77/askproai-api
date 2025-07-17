<?php
/**
 * Script zur Datenvalidierung von Call-Daten
 * Prüft welche Daten in den Calls vorhanden sind und welche fehlen
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n=== CALL DATEN VOLLSTÄNDIGKEITS-CHECK ===\n";

// Optionaler Zeitraum-Parameter
$days = isset($argv[1]) ? intval($argv[1]) : 30;
echo "Zeitraum: Letzte $days Tage\n\n";

// Basis-Statistiken
$stats = DB::select("
    SELECT 
        COUNT(*) as total_calls,
        SUM(CASE WHEN transcript IS NOT NULL AND transcript != '' THEN 1 ELSE 0 END) as has_transcript,
        SUM(CASE WHEN recording_url IS NOT NULL AND recording_url != '' THEN 1 ELSE 0 END) as has_audio,
        SUM(CASE WHEN (summary IS NOT NULL AND summary != '') OR (call_summary IS NOT NULL AND call_summary != '') THEN 1 ELSE 0 END) as has_summary,
        SUM(CASE WHEN extracted_name IS NOT NULL AND extracted_name != '' THEN 1 ELSE 0 END) as has_name,
        SUM(CASE WHEN extracted_email IS NOT NULL AND extracted_email != '' THEN 1 ELSE 0 END) as has_email,
        SUM(CASE WHEN phone_number IS NOT NULL AND phone_number != '' THEN 1 ELSE 0 END) as has_phone,
        SUM(CASE WHEN custom_analysis_data IS NOT NULL AND JSON_LENGTH(custom_analysis_data) > 0 THEN 1 ELSE 0 END) as has_analysis,
        SUM(CASE WHEN datum_termin IS NOT NULL THEN 1 ELSE 0 END) as has_appointment_date,
        SUM(CASE WHEN uhrzeit_termin IS NOT NULL THEN 1 ELSE 0 END) as has_appointment_time,
        SUM(CASE WHEN dienstleistung IS NOT NULL AND dienstleistung != '' THEN 1 ELSE 0 END) as has_service,
        SUM(CASE WHEN urgency_level IS NOT NULL AND urgency_level != '' THEN 1 ELSE 0 END) as has_urgency,
        SUM(CASE WHEN reason_for_visit IS NOT NULL AND reason_for_visit != '' THEN 1 ELSE 0 END) as has_reason,
        SUM(CASE WHEN detected_language IS NOT NULL AND detected_language != '' THEN 1 ELSE 0 END) as has_language,
        SUM(CASE WHEN transcript_object IS NOT NULL AND JSON_LENGTH(transcript_object) > 0 THEN 1 ELSE 0 END) as has_structured_transcript
    FROM calls 
    WHERE status = 'ended' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
", [$days])[0];

echo "📊 ÜBERSICHT (Letzte $days Tage)\n";
echo str_repeat("-", 70) . "\n";
printf("%-40s: %d\n", "Gesamtanzahl Anrufe", $stats->total_calls);
echo "\n";

if ($stats->total_calls == 0) {
    echo "⚠️  Keine Anrufe im angegebenen Zeitraum gefunden!\n";
    echo "Versuchen Sie einen größeren Zeitraum mit: php check-call-data-completeness.php <tage>\n\n";
    exit(0);
}

echo "📝 INHALTSDATEN\n";
echo str_repeat("-", 70) . "\n";
printf("%-40s: %d (%.1f%%)\n", "Mit Transkript", $stats->has_transcript, ($stats->has_transcript / $stats->total_calls) * 100);
printf("%-40s: %d (%.1f%%)\n", "Mit Audio-URL", $stats->has_audio, ($stats->has_audio / $stats->total_calls) * 100);
printf("%-40s: %d (%.1f%%)\n", "Mit Zusammenfassung", $stats->has_summary, ($stats->has_summary / $stats->total_calls) * 100);
printf("%-40s: %d (%.1f%%)\n", "Mit strukturiertem Transkript", $stats->has_structured_transcript, ($stats->has_structured_transcript / $stats->total_calls) * 100);
echo "\n";

echo "👤 KUNDENDATEN\n";
echo str_repeat("-", 70) . "\n";
printf("%-40s: %d (%.1f%%)\n", "Mit Kundenname", $stats->has_name, ($stats->has_name / $stats->total_calls) * 100);
printf("%-40s: %d (%.1f%%)\n", "Mit Email", $stats->has_email, ($stats->has_email / $stats->total_calls) * 100);
printf("%-40s: %d (%.1f%%)\n", "Mit Telefonnummer", $stats->has_phone, ($stats->has_phone / $stats->total_calls) * 100);
printf("%-40s: %d (%.1f%%)\n", "Mit Anrufgrund", $stats->has_reason, ($stats->has_reason / $stats->total_calls) * 100);
echo "\n";

echo "📅 TERMINDATEN\n";
echo str_repeat("-", 70) . "\n";
printf("%-40s: %d (%.1f%%)\n", "Mit Terminwunsch Datum", $stats->has_appointment_date, ($stats->has_appointment_date / $stats->total_calls) * 100);
printf("%-40s: %d (%.1f%%)\n", "Mit Terminwunsch Uhrzeit", $stats->has_appointment_time, ($stats->has_appointment_time / $stats->total_calls) * 100);
printf("%-40s: %d (%.1f%%)\n", "Mit Dienstleistung", $stats->has_service, ($stats->has_service / $stats->total_calls) * 100);
echo "\n";

echo "🔍 ANALYSE-DATEN\n";
echo str_repeat("-", 70) . "\n";
printf("%-40s: %d (%.1f%%)\n", "Mit Custom Analysis Data", $stats->has_analysis, ($stats->has_analysis / $stats->total_calls) * 100);
printf("%-40s: %d (%.1f%%)\n", "Mit Dringlichkeit", $stats->has_urgency, ($stats->has_urgency / $stats->total_calls) * 100);
printf("%-40s: %d (%.1f%%)\n", "Mit erkannter Sprache", $stats->has_language, ($stats->has_language / $stats->total_calls) * 100);
echo "\n";

// Detaillierte Analyse der letzten 10 Calls ohne Audio
echo "❌ CALLS OHNE AUDIO (Letzte 10)\n";
echo str_repeat("-", 70) . "\n";
$callsWithoutAudio = DB::select("
    SELECT id, call_id, DATE_FORMAT(created_at, '%d.%m.%Y %H:%i') as created_at_formatted, 
           from_number, duration_sec,
           CASE WHEN transcript IS NOT NULL THEN 'Ja' ELSE 'Nein' END as has_transcript
    FROM calls 
    WHERE (recording_url IS NULL OR recording_url = '')
    AND status = 'ended'
    ORDER BY created_at DESC 
    LIMIT 10
");

if (count($callsWithoutAudio) > 0) {
    printf("%-10s %-20s %-15s %-10s %-10s\n", "ID", "Datum/Zeit", "Von", "Dauer", "Transkript");
    echo str_repeat("-", 70) . "\n";
    foreach ($callsWithoutAudio as $call) {
        printf("%-10s %-20s %-15s %-10s %-10s\n", 
            $call->id, 
            $call->created_at_formatted, 
            substr($call->from_number ?? 'Unknown', -10),
            $call->duration_sec . 's',
            $call->has_transcript
        );
    }
} else {
    echo "Keine Calls ohne Audio gefunden!\n";
}
echo "\n";

// Analyse der custom_analysis_data Felder
echo "📊 CUSTOM ANALYSIS DATA FELDER (Top 20)\n";
echo str_repeat("-", 70) . "\n";
$customFields = DB::select("
    SELECT 
        JSON_KEYS(custom_analysis_data) as field_keys,
        COUNT(*) as count
    FROM calls 
    WHERE custom_analysis_data IS NOT NULL 
    AND JSON_LENGTH(custom_analysis_data) > 0
    AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY JSON_KEYS(custom_analysis_data)
    ORDER BY count DESC
    LIMIT 20
", [$days]);

$fieldCounts = [];
foreach ($customFields as $row) {
    $keys = json_decode($row->field_keys);
    if (is_array($keys)) {
        foreach ($keys as $key) {
            if (!isset($fieldCounts[$key])) {
                $fieldCounts[$key] = 0;
            }
            $fieldCounts[$key] += $row->count;
        }
    }
}
arsort($fieldCounts);

foreach (array_slice($fieldCounts, 0, 20) as $field => $count) {
    printf("%-40s: %d\n", $field, $count);
}
echo "\n";

// Empfehlungen
echo "💡 EMPFEHLUNGEN\n";
echo str_repeat("=", 70) . "\n";

$issues = [];
if (($stats->has_audio / $stats->total_calls) < 0.9) {
    $issues[] = "- Audio-URLs fehlen bei " . round((1 - $stats->has_audio / $stats->total_calls) * 100, 1) . "% der Calls";
}
if (($stats->has_name / $stats->total_calls) < 0.7) {
    $issues[] = "- Kundennamen fehlen bei " . round((1 - $stats->has_name / $stats->total_calls) * 100, 1) . "% der Calls";
}
if (($stats->has_summary / $stats->total_calls) < 0.8) {
    $issues[] = "- Zusammenfassungen fehlen bei " . round((1 - $stats->has_summary / $stats->total_calls) * 100, 1) . "% der Calls";
}

if (count($issues) > 0) {
    echo "Folgende Probleme wurden identifiziert:\n\n";
    foreach ($issues as $issue) {
        echo $issue . "\n";
    }
    echo "\nEmpfohlene Maßnahmen:\n";
    echo "1. Retell.ai Webhook-Konfiguration prüfen\n";
    echo "2. Agent-Konfiguration für Datenextraktion überprüfen\n";
    echo "3. Reprocessing-Script für fehlende Daten ausführen\n";
} else {
    echo "✅ Datenqualität ist gut! Alle wichtigen Felder sind bei >70% der Calls vorhanden.\n";
}

echo "\n=== ENDE DES REPORTS ===\n\n";