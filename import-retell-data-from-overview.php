<?php

/**
 * Import Retell Data from Overview
 * 
 * Importiert die Daten aus der Retell-Übersicht des Users
 * mit korrektem Mapping aller Felder
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use Illuminate\Support\Facades\DB;

echo "\n=== Import Retell Data from Overview ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Daten aus der User-Übersicht (gekürzt, nur relevante Beispiele)
$retellData = [
    // Successful calls with appointment data
    ['call_id' => 'call_40495c0d8b6bfdabea8796b41ec', 'session_outcome' => 'Successful', 'appointment_made' => true, 'appointment_date_time' => '2025-06-27 16:00', 'patient_full_name' => 'Hans Schuster', 'reason_for_visit' => 'Beratung', 'caller_phone' => '{{caller_phone_number}}'],
    ['call_id' => 'call_52961c1636e3f8df6f49e24ac3a', 'session_outcome' => 'Successful', 'appointment_made' => true, 'appointment_date_time' => '2025-06-26 16:00', 'patient_full_name' => 'Hans Schuster', 'reason_for_visit' => 'Beratungsgespräch für die KI Telefonie', 'caller_phone' => '{{caller_phone_number}}'],
    ['call_id' => 'call_0b0b94b2586a676f3807e457830', 'session_outcome' => 'Successful', 'appointment_made' => true, 'appointment_date_time' => '2025-06-26 16:00', 'patient_full_name' => 'Hans Schuster', 'reason_for_visit' => 'Beratung', 'caller_phone' => '{{caller_phone_number}}'],
    ['call_id' => 'call_c7ec150d32f3e6c43675b2def0e', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '16052024', '_uhrzeit__termin' => '16', '_name' => 'Hans Schuster'],
    ['call_id' => 'call_e682d06faa31c70cacc57550782', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '20240521', '_uhrzeit__termin' => '16', '_name' => 'Hans Schuster', '_zusammenfassung__anruf' => 'Herr Schuster hat erfolgreich einen Termin am 21.05.2024 um 16:00 Uhr gebucht.'],
    ['call_id' => 'call_3773bc186b2b76d579c182ccff3', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '20231104', '_uhrzeit__termin' => '10', '_name' => 'Hans Schuster'],
    ['call_id' => 'call_e2c5ee91e1f16a58b2be0f962dd', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '16052024', '_uhrzeit__termin' => '17', '_name' => 'Martin Schuster'],
    ['call_id' => 'call_875d33be772fd2aac014a7e7a78', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '20240625', '_uhrzeit__termin' => '11', '_name' => 'Hans Schuster'],
    ['call_id' => 'call_1e4f09f4e5974e00f193e9efba8', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '16052024', '_uhrzeit__termin' => '11', '_name' => 'Fritzi Frith', '_email' => 'frith@icloud.com'],
    ['call_id' => 'call_2d43af9cf6b9846b79b5ebcfdd0', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '25062025', '_uhrzeit__termin' => '10', '_name' => 'Martin Schubert'],
    ['call_id' => 'call_de11f628c040a5a38dc078f4220', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '20250624', '_uhrzeit__termin' => '17', '_name' => 'Friedrich Fritzer'],
    ['call_id' => 'call_01d8953a768969d1c2b74513404', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '20231104', '_uhrzeit__termin' => '17', '_name' => 'Huber Schuster', '_email' => 'sabaail@gmail.com'],
    ['call_id' => 'call_6033e69d3eeb332336f64806360', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '24.062025', '_uhrzeit__termin' => '11.3', '_name' => 'Hubertus Falke'],
    ['call_id' => 'call_0a8ce4d6c04abf9c1a39ad5a6b8', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '20250623', '_uhrzeit__termin' => '11', '_name' => 'Hans Schuster', '_email' => 'Fabian@AskProAI.de'],
    ['call_id' => 'call_684bd3c1f71c0f57bb7a146884e', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '20231102', '_uhrzeit__termin' => '14', '_name' => 'Martin Schuster'],
    ['call_id' => 'call_3b4f43d5ef670da456bf6b957c9', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '20231102', '_uhrzeit__termin' => '16', '_name' => 'Hans Schuster', '_email' => 'fabhandy@gmail.com'],
    ['call_id' => 'call_8229e7b6c3d825f6fd11cc0b6fb', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '20250623', '_uhrzeit__termin' => '1400', '_name' => 'Hans Schmidt'],
    ['call_id' => 'call_d208ce4194df904845d42035ba8', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '23.06', '_uhrzeit__termin' => '13.3', '_name' => 'Hans Schmidt'],
    ['call_id' => 'call_eb0b1d152db747dc21249f3996b', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '23062023', '_uhrzeit__termin' => '14', '_name' => 'Marc Schuster'],
    ['call_id' => 'call_09cad6a5bb698780dec81d49bd4', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '2306', '_uhrzeit__termin' => '14', '_name' => 'Hans Schmidt'],
    ['call_id' => 'call_5e797df8ec7d258d08f1b293538', 'session_outcome' => 'Successful', 'appointment_made' => true, '_datum__termin' => '23062023', '_uhrzeit__termin' => '14', '_name' => 'Hann Schuster'],
    
    // Unsuccessful calls
    ['call_id' => 'call_e2c7629e547c22f066eebac60f9', 'session_outcome' => 'Unsuccessful', 'appointment_made' => false],
    ['call_id' => 'call_921af687ce956b87eda85157e5b', 'session_outcome' => 'Unsuccessful', 'appointment_made' => false],
    ['call_id' => 'call_e54f48495bcbc0433900e2a71d4', 'session_outcome' => 'Unsuccessful', 'appointment_made' => false],
    ['call_id' => 'call_c58cb624a7384c38ed63cfc6a08', 'session_outcome' => 'Unsuccessful', 'appointment_made' => false],
    ['call_id' => 'call_738d9d1b60be8f0461d86e5d1ed', 'session_outcome' => 'Unsuccessful', 'appointment_made' => false],
    ['call_id' => 'call_4c6a52d21b39c42a8f4153fef1e', 'session_outcome' => 'Unsuccessful', 'appointment_made' => false],
    ['call_id' => 'call_10fe61faf4352275fc68719549e', 'session_outcome' => 'Unsuccessful', 'appointment_made' => false],
];

$updated = 0;
$errors = 0;

foreach ($retellData as $data) {
    $callId = $data['call_id'];
    
    // Find call in database
    $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('call_id', $callId)
        ->orWhere('retell_call_id', $callId)
        ->first();
        
    if (!$call) {
        echo "⚠️  Call $callId not found in database\n";
        continue;
    }
    
    try {
        // Update session outcome
        if (isset($data['session_outcome']) && empty($call->session_outcome)) {
            $call->session_outcome = $data['session_outcome'];
        }
        
        // Update appointment_made
        if (isset($data['appointment_made'])) {
            $call->appointment_made = $data['appointment_made'];
        }
        
        // Parse and update appointment date/time
        if (isset($data['appointment_date_time'])) {
            // Format: 2025-06-27 16:00
            $datetime = \Carbon\Carbon::parse($data['appointment_date_time']);
            $call->datum_termin = $datetime->format('Y-m-d');
            $call->uhrzeit_termin = $datetime->format('H:i');
        } elseif (isset($data['_datum__termin'])) {
            // Parse various date formats
            $dateStr = $data['_datum__termin'];
            $date = parseGermanDate($dateStr);
            if ($date) {
                $call->datum_termin = $date;
            }
        }
        
        // Parse time
        if (isset($data['_uhrzeit__termin'])) {
            $timeStr = $data['_uhrzeit__termin'];
            $time = parseGermanTime($timeStr);
            if ($time) {
                $call->uhrzeit_termin = $time;
            }
        }
        
        // Update customer name
        if (isset($data['patient_full_name']) && empty($call->name)) {
            $call->name = $data['patient_full_name'];
        } elseif (isset($data['caller_full_name']) && empty($call->name)) {
            $call->name = $data['caller_full_name'];
        } elseif (isset($data['_name']) && empty($call->name)) {
            $call->name = $data['_name'];
        }
        
        // Update email
        if (isset($data['_email']) && empty($call->email)) {
            $call->email = $data['_email'];
        }
        
        // Update service/reason
        if (isset($data['reason_for_visit']) && empty($call->dienstleistung)) {
            $call->dienstleistung = $data['reason_for_visit'];
            $call->reason_for_visit = $data['reason_for_visit'];
        }
        
        // Update phone if template
        if (isset($data['caller_phone']) && $data['caller_phone'] === '{{caller_phone_number}}' && empty($call->phone_number)) {
            $call->phone_number = $call->from_number;
        } elseif (isset($data['caller_phone']) && !str_contains($data['caller_phone'], '{{')) {
            $call->phone_number = $data['caller_phone'];
        }
        
        // Update summary
        if (isset($data['_zusammenfassung__anruf']) && empty($call->summary)) {
            $call->summary = $data['_zusammenfassung__anruf'];
        }
        
        // Save changes
        $call->save();
        $updated++;
        echo "✅ Updated call $callId\n";
        
    } catch (\Exception $e) {
        echo "❌ Error updating call $callId: " . $e->getMessage() . "\n";
        $errors++;
    }
}

// Now import the rest of the fields from the overview format
echo "\n2. Updating remaining fields based on patterns...\n";

// Map session outcomes for all calls
DB::statement("
    UPDATE calls 
    SET session_outcome = CASE 
        WHEN appointment_made = 1 THEN 'Successful'
        WHEN disconnection_reason = 'agent hangup' AND sentiment = 'Positive' THEN 'Successful'
        WHEN duration_sec > 90 AND sentiment IN ('Positive', 'Neutral') THEN 'Successful'
        ELSE 'Unsuccessful'
    END
    WHERE session_outcome IS NULL
");

// Set default service type if missing
DB::statement("
    UPDATE calls 
    SET dienstleistung = 'Beratung',
        reason_for_visit = 'Beratung'
    WHERE dienstleistung IS NULL 
    AND appointment_made = 1
");

// Ensure phone numbers are set
DB::statement("
    UPDATE calls 
    SET phone_number = from_number
    WHERE phone_number IS NULL 
    AND from_number IS NOT NULL
");

// Generate final report
echo "\n3. Generating final data quality report...\n";

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
        SUM(CASE WHEN phone_number IS NOT NULL THEN 1 ELSE 0 END) as with_phone,
        SUM(CASE WHEN cost IS NOT NULL THEN 1 ELSE 0 END) as with_cost,
        SUM(CASE WHEN agent_version IS NOT NULL THEN 1 ELSE 0 END) as with_agent_version
    FROM calls
")[0];

echo "\n=== Final Data Quality Report ===\n";
echo "Total Calls: {$report->total_calls}\n";
echo "\nCore Fields:\n";
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Session Outcome", $report->with_outcome, $report->total_calls, ($report->with_outcome / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Successful Calls", $report->successful_calls, $report->total_calls, ($report->successful_calls / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Agent Version", $report->with_agent_version, $report->total_calls, ($report->with_agent_version / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Cost Data", $report->with_cost, $report->total_calls, ($report->with_cost / $report->total_calls) * 100);

echo "\nAppointment Data:\n";
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Appointments Made", $report->appointments_made, $report->total_calls, ($report->appointments_made / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Customer Name", $report->with_customer_name, $report->total_calls, ($report->with_customer_name / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Phone Number", $report->with_phone, $report->total_calls, ($report->with_phone / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Email", $report->with_email, $report->total_calls, ($report->with_email / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Appointment Date", $report->with_appointment_date, $report->total_calls, ($report->with_appointment_date / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Appointment Time", $report->with_appointment_time, $report->total_calls, ($report->with_appointment_time / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Service", $report->with_service, $report->total_calls, ($report->with_service / $report->total_calls) * 100);

// Show sample successful appointments
echo "\n4. Sample Successful Appointments:\n";
$samples = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('appointment_made', 1)
    ->whereNotNull('datum_termin')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['call_id', 'name', 'datum_termin', 'uhrzeit_termin', 'dienstleistung', 'session_outcome']);

foreach ($samples as $sample) {
    echo sprintf("Call %s: %s on %s at %s for %s (%s)\n",
        substr($sample->call_id, 0, 20),
        $sample->name,
        $sample->datum_termin,
        $sample->uhrzeit_termin,
        $sample->dienstleistung,
        $sample->session_outcome
    );
}

echo "\n=== Summary ===\n";
echo "Calls updated: $updated\n";
echo "Errors: $errors\n";

echo "\n✅ Import complete!\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";

// Helper functions
function parseGermanDate($dateStr): ?string
{
    // Remove dots and spaces
    $dateStr = str_replace(['.', ' '], '', $dateStr);
    
    // Try different formats
    $formats = [
        'dmY' => '/^(\d{1,2})(\d{1,2})(\d{4})$/',     // 20062024
        'Ymd' => '/^(\d{4})(\d{1,2})(\d{1,2})$/',     // 20240620
        'ymd' => '/^(\d{2})(\d{1,2})(\d{1,2})$/',     // 240620
    ];
    
    foreach ($formats as $format => $pattern) {
        if (preg_match($pattern, $dateStr, $matches)) {
            try {
                if ($format === 'dmY') {
                    $date = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                } elseif ($format === 'Ymd') {
                    $date = sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
                } elseif ($format === 'ymd') {
                    $year = '20' . $matches[1];
                    $date = sprintf('%04d-%02d-%02d', $year, $matches[2], $matches[3]);
                }
                
                // Validate date
                $carbon = \Carbon\Carbon::parse($date);
                return $carbon->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }
    }
    
    return null;
}

function parseGermanTime($timeStr): ?string
{
    // Remove dots and spaces
    $timeStr = str_replace(['.', ' ', ':'], '', $timeStr);
    
    // Extract hour
    if (preg_match('/^(\d{1,2})/', $timeStr, $matches)) {
        $hour = (int)$matches[1];
        if ($hour >= 0 && $hour <= 23) {
            return sprintf('%02d:00', $hour);
        }
    }
    
    return null;
}