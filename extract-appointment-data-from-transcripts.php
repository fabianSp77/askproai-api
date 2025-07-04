<?php

/**
 * Extract Appointment Data from Transcripts
 * 
 * Da die Dynamic Variables nur Twilio-Daten enthalten,
 * müssen wir die Termindaten aus den Transkripten extrahieren
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use Illuminate\Support\Facades\DB;

echo "\n=== Extract Appointment Data from Transcripts ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Patterns für Datenextraktion aus Transkripten
$patterns = [
    'name' => [
        '/(?:heißen?|Namen?|ich bin|mein Name ist)\s+([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)/i',
        '/(?:Herr|Frau)\s+([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)/i',
    ],
    'datum' => [
        '/(\d{1,2}\.\d{1,2}\.(?:\d{4}|\d{2}))/i',
        '/(\d{1,2}\.\s*(?:Januar|Februar|März|April|Mai|Juni|Juli|August|September|Oktober|November|Dezember))/i',
        '/(Montag|Dienstag|Mittwoch|Donnerstag|Freitag|Samstag|Sonntag)/i',
        '/(morgen|übermorgen|nächste Woche)/i',
    ],
    'uhrzeit' => [
        '/(\d{1,2}:\d{2})\s*(?:Uhr)?/i',
        '/(\d{1,2})\s*Uhr/i',
        '/um\s+(\d{1,2})/i',
    ],
    'dienstleistung' => [
        '/(Beratung|Behandlung|Haarschnitt|Massage|Termin|Untersuchung|Kontrolle)/i',
    ],
    'telefon' => [
        '/(?:Telefon|Nummer|erreichen)\s*:?\s*(\+?[\d\s\-\/]+)/i',
        '/(\+49[\d\s]+)/i',
        '/(0\d{2,4}[\s\-]?[\d\s\-]+)/i',
    ],
    'email' => [
        '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i',
    ],
];

// Get all calls with transcripts but no appointment data
$calls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNotNull('transcript')
    ->where(function($query) {
        $query->whereNull('name')
              ->orWhereNull('datum_termin')
              ->orWhereNull('dienstleistung');
    })
    ->get();

echo "Found " . $calls->count() . " calls with transcripts to analyze\n\n";

$updated = 0;
$appointmentsFound = 0;

foreach ($calls as $call) {
    $transcript = $call->transcript;
    $summary = $call->summary ?? '';
    $analysis = $call->analysis ?? [];
    
    $extractedData = [];
    $updates = [];
    
    // Combine transcript and summary for better extraction
    $fullText = $transcript . ' ' . $summary;
    
    // Extract name
    if (empty($call->name)) {
        foreach ($patterns['name'] as $pattern) {
            if (preg_match($pattern, $fullText, $matches)) {
                $name = trim($matches[1]);
                if (strlen($name) > 3 && !in_array(strtolower($name), ['agent', 'assistent', 'system'])) {
                    $extractedData['name'] = $name;
                    $updates['name'] = $name;
                    break;
                }
            }
        }
    }
    
    // Extract date
    if (empty($call->datum_termin)) {
        foreach ($patterns['datum'] as $pattern) {
            if (preg_match($pattern, $fullText, $matches)) {
                $dateStr = $matches[1];
                $extractedData['datum'] = $dateStr;
                $updates['datum_termin'] = $dateStr;
                break;
            }
        }
    }
    
    // Extract time
    if (empty($call->uhrzeit_termin)) {
        foreach ($patterns['uhrzeit'] as $pattern) {
            if (preg_match($pattern, $fullText, $matches)) {
                $timeStr = $matches[1];
                // Normalize time format
                if (!str_contains($timeStr, ':')) {
                    $timeStr .= ':00';
                }
                $extractedData['uhrzeit'] = $timeStr;
                $updates['uhrzeit_termin'] = $timeStr;
                break;
            }
        }
    }
    
    // Extract service
    if (empty($call->dienstleistung)) {
        foreach ($patterns['dienstleistung'] as $pattern) {
            if (preg_match($pattern, $fullText, $matches)) {
                $service = $matches[1];
                $extractedData['dienstleistung'] = $service;
                $updates['dienstleistung'] = $service;
                $updates['reason_for_visit'] = $service;
                break;
            }
        }
    }
    
    // Extract phone if missing
    if (empty($call->phone_number) && $call->from_number) {
        $updates['phone_number'] = $call->from_number;
    }
    
    // Extract email
    if (empty($call->email)) {
        foreach ($patterns['email'] as $pattern) {
            if (preg_match($pattern, $fullText, $matches)) {
                $email = strtolower($matches[1]);
                $extractedData['email'] = $email;
                $updates['email'] = $email;
                break;
            }
        }
    }
    
    // Determine session outcome based on extracted data and call status
    if (empty($call->session_outcome)) {
        $hasAppointmentData = !empty($extractedData['datum']) && 
                             !empty($extractedData['uhrzeit']) && 
                             !empty($extractedData['dienstleistung']);
        
        // Check if summary indicates success
        $successKeywords = ['erfolgreich', 'gebucht', 'vereinbart', 'bestätigt', 'Termin am'];
        $failureKeywords = ['abgebrochen', 'nicht', 'kein Termin', 'aufgelegt', 'beendet'];
        
        $summaryLower = strtolower($summary);
        $isSuccessful = false;
        
        foreach ($successKeywords as $keyword) {
            if (str_contains($summaryLower, $keyword)) {
                $isSuccessful = true;
                break;
            }
        }
        
        foreach ($failureKeywords as $keyword) {
            if (str_contains($summaryLower, $keyword)) {
                $isSuccessful = false;
                break;
            }
        }
        
        $updates['session_outcome'] = ($hasAppointmentData || $isSuccessful) ? 'Successful' : 'Unsuccessful';
    }
    
    // Set appointment_made flag
    if (!empty($extractedData['datum']) && !empty($extractedData['uhrzeit']) && !empty($extractedData['dienstleistung'])) {
        $updates['appointment_made'] = true;
        $appointmentsFound++;
    }
    
    // Apply updates
    if (!empty($updates)) {
        try {
            foreach ($updates as $field => $value) {
                $call->$field = $value;
            }
            $call->save();
            
            $updated++;
            echo "✅ Call {$call->call_id}: Extracted " . count($extractedData) . " fields\n";
            if (!empty($extractedData)) {
                foreach ($extractedData as $key => $value) {
                    echo "   - $key: $value\n";
                }
            }
            
        } catch (\Exception $e) {
            echo "❌ Error updating call {$call->id}: " . $e->getMessage() . "\n";
        }
    }
}

// Now update session_outcome for calls based on the data from your overview
echo "\n2. Updating session outcomes based on call characteristics...\n";

// Update calls with complete appointment data as Successful
DB::statement("
    UPDATE calls 
    SET session_outcome = 'Successful',
        appointment_made = 1
    WHERE datum_termin IS NOT NULL 
    AND uhrzeit_termin IS NOT NULL 
    AND dienstleistung IS NOT NULL
    AND name IS NOT NULL
    AND session_outcome IS NULL
");

// Update calls with agent hangup and positive sentiment as Successful
DB::statement("
    UPDATE calls 
    SET session_outcome = 'Successful'
    WHERE disconnection_reason = 'agent hangup'
    AND sentiment = 'Positive'
    AND session_outcome IS NULL
");

// Update calls with user hangup and no appointment data as Unsuccessful
DB::statement("
    UPDATE calls 
    SET session_outcome = 'Unsuccessful'
    WHERE disconnection_reason = 'user hangup'
    AND (datum_termin IS NULL OR uhrzeit_termin IS NULL)
    AND session_outcome IS NULL
");

// Set remaining null outcomes based on duration and sentiment
DB::statement("
    UPDATE calls 
    SET session_outcome = CASE 
        WHEN duration_sec > 90 AND sentiment IN ('Positive', 'Neutral') THEN 'Successful'
        ELSE 'Unsuccessful'
    END
    WHERE session_outcome IS NULL
");

// Generate updated report
echo "\n3. Generating updated data quality report...\n";

$report = DB::select("
    SELECT 
        COUNT(*) as total_calls,
        SUM(CASE WHEN session_outcome IS NOT NULL THEN 1 ELSE 0 END) as with_outcome,
        SUM(CASE WHEN session_outcome = 'Successful' THEN 1 ELSE 0 END) as successful_calls,
        SUM(CASE WHEN appointment_made = 1 THEN 1 ELSE 0 END) as appointments_made,
        SUM(CASE WHEN name IS NOT NULL THEN 1 ELSE 0 END) as with_customer_name,
        SUM(CASE WHEN email IS NOT NULL THEN 1 ELSE 0 END) as with_email,
        SUM(CASE WHEN datum_termin IS NOT NULL THEN 1 ELSE 0 END) as with_appointment_date,
        SUM(CASE WHEN uhrzeit_termin IS NOT NULL THEN 1 ELSE 0 END) as with_appointment_time,
        SUM(CASE WHEN dienstleistung IS NOT NULL THEN 1 ELSE 0 END) as with_service,
        SUM(CASE WHEN phone_number IS NOT NULL THEN 1 ELSE 0 END) as with_phone
    FROM calls
")[0];

echo "\n=== Updated Data Quality Report ===\n";
echo "Total Calls: {$report->total_calls}\n";
echo "\nSession Outcomes:\n";
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "With Outcome", $report->with_outcome, $report->total_calls, ($report->with_outcome / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Successful Calls", $report->successful_calls, $report->total_calls, ($report->successful_calls / $report->total_calls) * 100);

echo "\nAppointment Data:\n";
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Appointments Made", $report->appointments_made, $report->total_calls, ($report->appointments_made / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Customer Name", $report->with_customer_name, $report->total_calls, ($report->with_customer_name / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Phone Number", $report->with_phone, $report->total_calls, ($report->with_phone / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Email", $report->with_email, $report->total_calls, ($report->with_email / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Appointment Date", $report->with_appointment_date, $report->total_calls, ($report->with_appointment_date / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Appointment Time", $report->with_appointment_time, $report->total_calls, ($report->with_appointment_time / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Service", $report->with_service, $report->total_calls, ($report->with_service / $report->total_calls) * 100);

echo "\n=== Summary ===\n";
echo "Calls analyzed: " . $calls->count() . "\n";
echo "Calls updated: $updated\n";
echo "Appointments found: $appointmentsFound\n";

echo "\n✅ Extraction complete!\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";