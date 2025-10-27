<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  COMPLETE CALL ANALYSIS - Schritt für Schritt             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$callData = json_decode(file_get_contents('/tmp/latest_test_call.json'), true);
$callId = $callData['call_id'];

echo "=== CALL OVERVIEW ===\n\n";
echo "Call ID: {$callId}\n";
echo "Agent: " . ($callData['agent_id'] ?? 'N/A') . " (v" . ($callData['agent_version'] ?? 'N/A') . ")\n";
echo "Duration: " . round(($callData['duration_ms'] ?? 0) / 1000, 1) . "s\n";
echo "Start: " . ($callData['start_timestamp'] ?? 'N/A') . "\n";
echo "End: " . ($callData['end_timestamp'] ?? 'N/A') . "\n";
echo "Disconnect: " . ($callData['disconnection_reason'] ?? 'N/A') . "\n\n";

// Get customer ID from database
$dbCall = DB::table('calls')->where('external_id', $callId)->first();
$customerId = $dbCall->customer_id ?? null;

echo "Customer ID (DB): {$customerId}\n\n";

// Extract transcript text
$transcript = $callData['transcript'] ?? '';

echo "=== TRANSCRIPT (showing key parts) ===\n\n";
echo substr($transcript, 0, 500) . "\n...\n";
echo substr($transcript, -500) . "\n\n";

// Save full transcript
file_put_contents('/tmp/full_transcript.txt', $transcript);
echo "✅ Full transcript saved to: /tmp/full_transcript.txt\n\n";

// Extract function calls from transcript
echo "=== FUNCTION CALLS ANALYSIS ===\n\n";

// Search for function call patterns in transcript
preg_match_all('/\[([\d.]+)s\]\s+(\w+)\s+\((.*?)\)/s', $transcript, $matches, PREG_SET_ORDER);

$checkAvailabilityCalls = 0;
$bookAppointmentCalls = 0;

echo "Searching transcript for function calls...\n\n";

// Look for specific function mentions
if (strpos($transcript, 'check_availability') !== false) {
    echo "✅ Found 'check_availability' in transcript\n";
    $checkAvailabilityCalls = substr_count($transcript, 'check_availability');
}

if (strpos($transcript, 'book_appointment') !== false) {
    echo "✅ Found 'book_appointment' in transcript\n";
    $bookAppointmentCalls = substr_count($transcript, 'book_appointment');
}

echo "\nFunction Call Count (from transcript text):\n";
echo "  - check_availability: ~{$checkAvailabilityCalls} mentions\n";
echo "  - book_appointment: ~{$bookAppointmentCalls} mentions\n\n";

// Check for specific patterns
echo "=== SPECIFIC PATTERN ANALYSIS ===\n\n";

// Look for availability confirmations
if (preg_match('/verfügbar|available/i', $transcript)) {
    echo "✅ Agent confirmed availability\n";
}

// Look for booking confirmations
if (preg_match('/gebucht|booked|erfolgreich|E-Mail|email/i', $transcript)) {
    echo "✅ Agent mentioned booking/email\n";
}

echo "\n";

// Check database for appointments
echo "=== DATABASE CHECK ===\n\n";

$appointments = DB::table('appointments')
    ->select('id', 'customer_id', 'service_type', 'appointment_date', 'appointment_time', 'status', 'calcom_booking_id', 'created_at')
    ->where('customer_id', $customerId)
    ->where('created_at', '>=', DB::raw("'2025-10-23 19:15:00'::timestamp"))
    ->orderBy('created_at', 'DESC')
    ->get();

if ($appointments->count() > 0) {
    echo "✅ APPOINTMENTS FOUND IN DATABASE:\n\n";
    foreach ($appointments as $appt) {
        echo "  Appointment ID: {$appt->id}\n";
        echo "    Customer: {$appt->customer_id}\n";
        echo "    Service: {$appt->service_type}\n";
        echo "    Datum: {$appt->appointment_date}\n";
        echo "    Uhrzeit: {$appt->appointment_time}\n";
        echo "    Status: {$appt->status}\n";
        echo "    Cal.com ID: " . ($appt->calcom_booking_id ?? '❌ NULL (NICHT GESYNCT!)') . "\n";
        echo "    Created: {$appt->created_at}\n";
        echo "\n";
    }
} else {
    echo "❌ KEINE APPOINTMENTS IN DATENBANK!\n\n";
    echo "   KRITISCH: Agent sagte er hat gebucht, aber:\n";
    echo "   - Kein Eintrag in 'appointments' Tabelle\n";
    echo "   - book_appointment_v17 wurde möglicherweise NICHT ausgeführt\n";
    echo "   - ODER es gab einen Fehler während der Buchung\n\n";
}

// Check logs for errors
echo "=== LOG CHECK (last 30 lines with book/appointment) ===\n\n";

$logFile = storage_path('logs/laravel.log');
$logContent = shell_exec("tail -100 {$logFile} | grep -i 'book\|appointment\|error' | tail -30");

if ($logContent) {
    echo $logContent . "\n";
} else {
    echo "No relevant log entries\n\n";
}

// Final analysis
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSE                                                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if ($appointments->count() === 0) {
    echo "🚨 PROBLEM BESTÄTIGT:\n\n";
    echo "Der Agent hat behauptet zu buchen, aber:\n\n";
    echo "1. ❌ Keine Appointment in Datenbank\n";
    echo "2. ❌ Keine Cal.com Synchronisation\n";
    echo "3. ❌ Kein Kalendereintrag\n\n";
    echo "MÖGLICHE URSACHEN:\n\n";
    echo "a) book_appointment_v17 Function wurde NICHT aufgerufen\n";
    echo "   -> Retell AI hat Function Call nicht gemacht\n";
    echo "   -> Edge condition nicht erfüllt\n\n";
    echo "b) book_appointment_v17 wurde aufgerufen, aber:\n";
    echo "   -> Function hat Error zurückgegeben\n";
    echo "   -> Datenbank-Insert fehlgeschlagen\n";
    echo "   -> Exception während Ausführung\n\n";
    echo "c) Netzwerk/API Problem:\n";
    echo "   -> Webhook nicht angekommen\n";
    echo "   -> Request timeout\n\n";
    echo "NEXT STEPS:\n";
    echo "1. Prüfe Laravel logs für Errors um 19:19 Uhr\n";
    echo "2. Prüfe ob book_appointment_v17 Webhook ankam\n";
    echo "3. Prüfe Retell AI dashboard für function call status\n\n";
} else {
    $appt = $appointments->first();
    echo "✅ Appointment wurde erstellt!\n\n";

    if (!$appt->calcom_booking_id) {
        echo "⚠️  ABER: Keine Cal.com Synchronisation!\n\n";
        echo "URSACHE:\n";
        echo "- SyncToCalcomJob nicht ausgeführt oder fehlgeschlagen\n";
        echo "- Queue worker nicht aktiv\n";
        echo "- Cal.com API Error\n\n";
    }
}
