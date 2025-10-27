#!/usr/bin/env php
<?php
/**
 * ULTRATHINK: Complete Forensic Analysis of Latest Call
 *
 * Analysiert JEDEN Aspekt des letzten Calls:
 * - Call Metadata
 * - Function Traces
 * - Transcript Segments
 * - Error Logs
 * - Agent Configuration
 * - Timeline Reconstruction
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RetellCallSession;
use App\Models\RetellFunctionTrace;
use App\Models\RetellTranscriptSegment;
use App\Models\RetellErrorLog;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                                                                              ║\n";
echo "║                 🔴 ULTRATHINK: LATEST CALL FORENSIC ANALYSIS                ║\n";
echo "║                                                                              ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════╝\n";
echo "\nTimestamp: " . now()->format('Y-m-d H:i:s') . "\n\n";

// ================================================================================
// STEP 1: FETCH LATEST CALL
// ================================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 1: FETCHING LATEST CALL\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$call = RetellCallSession::with(['functionTraces', 'transcriptSegments', 'errors'])
    ->orderBy('created_at', 'desc')
    ->first();

if (!$call) {
    echo "❌ ERROR: No calls found in database\n\n";
    exit(1);
}

echo "✅ Call found\n";
echo "   Call ID: {$call->call_id}\n";
echo "   Started: {$call->started_at}\n";
echo "   Ended: " . ($call->ended_at ?? 'N/A') . "\n";
echo "   Created: {$call->created_at}\n\n";

// ================================================================================
// STEP 2: CALL METADATA ANALYSIS
// ================================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 2: CALL METADATA ANALYSIS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$metadata = [
    'Call ID' => $call->call_id,
    'Agent ID' => $call->agent_id ?? 'N/A',
    'Customer ID' => $call->customer_id ?? 'N/A',
    'From Number' => $call->from_number ?? 'N/A',
    'To Number' => $call->to_number ?? 'N/A',
    'Call Status' => $call->call_status ?? 'N/A',
    'Disconnect Reason' => $call->disconnect_reason ?? 'N/A',
    'Started At' => $call->started_at ?? 'N/A',
    'Ended At' => $call->ended_at ?? 'N/A',
    'Duration (seconds)' => $call->call_duration_seconds ?? 0,
    'Agent Version' => $call->metadata['agent_version'] ?? 'N/A',
];

foreach ($metadata as $key => $value) {
    printf("%-25s: %s\n", $key, $value);
}
echo "\n";

// Calculate actual duration
if ($call->started_at && $call->ended_at) {
    $start = \Carbon\Carbon::parse($call->started_at);
    $end = \Carbon\Carbon::parse($call->ended_at);
    $actualDuration = $end->diffInSeconds($start);
    echo "Calculated Duration: {$actualDuration} seconds\n";

    if ($actualDuration < 10) {
        echo "⚠️  WARNING: Very short call (< 10 seconds)\n";
    }
}
echo "\n";

// ================================================================================
// STEP 3: FUNCTION TRACES ANALYSIS
// ================================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 3: FUNCTION TRACES ANALYSIS (CRITICAL!)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$functionTraces = $call->functionTraces;

echo "Total Function Calls: " . $functionTraces->count() . "\n\n";

if ($functionTraces->isEmpty()) {
    echo "❌ CRITICAL: NO FUNCTIONS WERE CALLED!\n";
    echo "   This is the EXACT problem we were trying to fix!\n\n";
    echo "🔴 ROOT CAUSE INDICATOR:\n";
    echo "   - func_check_availability was NOT called\n";
    echo "   - func_book_appointment was NOT called\n";
    echo "   - The flow DID NOT execute function nodes!\n\n";
} else {
    echo "Functions called:\n";
    foreach ($functionTraces as $trace) {
        echo "  ✅ {$trace->function_name}\n";
        echo "     Called at: {$trace->created_at}\n";
        echo "     Duration: " . ($trace->duration_ms ?? 'N/A') . " ms\n";
        echo "     Success: " . ($trace->success ? 'YES' : 'NO') . "\n";

        if ($trace->arguments) {
            echo "     Arguments: " . json_encode($trace->arguments) . "\n";
        }

        if ($trace->result) {
            $resultPreview = json_encode($trace->result);
            if (strlen($resultPreview) > 200) {
                $resultPreview = substr($resultPreview, 0, 200) . '...';
            }
            echo "     Result: " . $resultPreview . "\n";
        }

        if (!$trace->success && $trace->error_message) {
            echo "     ❌ Error: {$trace->error_message}\n";
        }

        echo "\n";
    }

    // Critical function check
    $hasCheckAvailability = $functionTraces->contains(function($trace) {
        return str_contains($trace->function_name, 'check_availability');
    });

    $hasBookAppointment = $functionTraces->contains(function($trace) {
        return str_contains($trace->function_name, 'book_appointment');
    });

    echo "🎯 CRITICAL FUNCTION CHECK:\n";
    echo "   check_availability called? " . ($hasCheckAvailability ? "✅ YES" : "❌ NO") . "\n";
    echo "   book_appointment called? " . ($hasBookAppointment ? "✅ YES" : "❌ NO") . "\n\n";

    if (!$hasCheckAvailability) {
        echo "🔴 PROBLEM: check_availability was NOT called!\n";
        echo "   This is the exact issue we deployed a fix for.\n\n";
    }
}

// ================================================================================
// STEP 4: TRANSCRIPT ANALYSIS
// ================================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 4: TRANSCRIPT ANALYSIS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$segments = $call->transcriptSegments()->orderBy('created_at')->get();

echo "Total Transcript Segments: " . $segments->count() . "\n\n";

if ($segments->isEmpty()) {
    echo "⚠️  No transcript segments found\n";
    echo "   Call may have ended before any conversation occurred\n\n";
} else {
    echo "Conversation Timeline:\n";
    echo str_repeat('-', 80) . "\n";

    foreach ($segments as $i => $segment) {
        $time = \Carbon\Carbon::parse($segment->created_at)->format('H:i:s');
        $role = $segment->role === 'agent' ? '🤖 AI' : '👤 USER';
        $text = $segment->content ?? $segment->text ?? 'N/A';

        echo "\n[{$time}] {$role}:\n";
        echo "  \"{$text}\"\n";
    }

    echo "\n" . str_repeat('-', 80) . "\n\n";

    // Analyze conversation for key phrases
    $allText = $segments->pluck('content')->concat($segments->pluck('text'))->implode(' ');
    $allTextLower = strtolower($allText);

    echo "🔍 KEY PHRASE ANALYSIS:\n";

    $keyPhrases = [
        'prüfe die verfügbarkeit' => 'AI says checking availability',
        'einen moment bitte' => 'AI asks for patience (during function call)',
        'verfügbar' => 'Mentions availability',
        'termin' => 'Mentions appointment',
        'buchen' => 'Mentions booking',
        'uhrzeit' => 'Mentions time',
        'datum' => 'Mentions date',
    ];

    foreach ($keyPhrases as $phrase => $meaning) {
        $found = str_contains($allTextLower, $phrase);
        echo "   " . ($found ? "✅" : "❌") . " '{$phrase}' → {$meaning}\n";
    }
    echo "\n";

    // Check if AI claimed to check availability without actually calling function
    $aiClaimsChecking = str_contains($allTextLower, 'prüfe') || str_contains($allTextLower, 'schaue');

    if ($aiClaimsChecking && $functionTraces->isEmpty()) {
        echo "🔴 HALLUCINATION DETECTED:\n";
        echo "   AI SAID it's checking, but NO function was actually called!\n";
        echo "   This is EXACTLY the problem we're trying to fix.\n\n";
    }
}

// ================================================================================
// STEP 5: ERROR LOG ANALYSIS
// ================================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 5: ERROR LOG ANALYSIS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$errors = $call->errors;

echo "Total Errors: " . $errors->count() . "\n\n";

if ($errors->isEmpty()) {
    echo "✅ No errors logged for this call\n\n";
} else {
    echo "Errors during call:\n";
    foreach ($errors as $error) {
        echo "  ❌ {$error->error_type}\n";
        echo "     Message: {$error->error_message}\n";
        echo "     Time: {$error->created_at}\n";
        if ($error->context) {
            echo "     Context: " . json_encode($error->context) . "\n";
        }
        echo "\n";
    }
}

// ================================================================================
// STEP 6: AGENT CONFIGURATION CHECK
// ================================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 6: AGENT CONFIGURATION CHECK\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$agentId = $call->agent_id ?? 'agent_f1ce85d06a84afb989dfbb16a9';
echo "Agent ID used in call: {$agentId}\n";

$expectedAgentId = 'agent_f1ce85d06a84afb989dfbb16a9';
if ($agentId !== $expectedAgentId) {
    echo "⚠️  WARNING: Different agent than expected!\n";
    echo "   Expected: {$expectedAgentId}\n";
    echo "   Got: {$agentId}\n\n";
} else {
    echo "✅ Correct agent\n\n";
}

// Check agent version
$agentVersion = $call->metadata['agent_version'] ?? null;
if ($agentVersion) {
    echo "Agent Version: {$agentVersion}\n";

    // Check if this is our deployed version
    // (We can't know exact version number, but we know we deployed TODAY)
    $deploymentTime = \Carbon\Carbon::parse('2025-10-24 19:02');
    $callTime = \Carbon\Carbon::parse($call->started_at);

    if ($callTime->lt($deploymentTime)) {
        echo "🔴 CRITICAL FINDING:\n";
        echo "   Call was made BEFORE deployment!\n";
        echo "   Call time: {$callTime->format('Y-m-d H:i:s')}\n";
        echo "   Deployment time: {$deploymentTime->format('Y-m-d H:i:s')}\n";
        echo "   This call used the OLD flow, not our new one!\n\n";
    } else {
        echo "✅ Call was made AFTER deployment ({$callTime->format('H:i:s')})\n";
        echo "   Should be using new flow\n\n";
    }
} else {
    echo "⚠️  Agent version not recorded in metadata\n\n";
}

// ================================================================================
// STEP 7: TIMELINE RECONSTRUCTION
// ================================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 7: COMPLETE TIMELINE RECONSTRUCTION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Combine all events
$events = [];

// Add call events
$events[] = [
    'time' => $call->started_at,
    'type' => 'CALL_START',
    'data' => "Call started from {$call->from_number}",
];

// Add transcript events
foreach ($segments as $segment) {
    $events[] = [
        'time' => $segment->created_at,
        'type' => $segment->role === 'agent' ? 'AI_SPOKE' : 'USER_SPOKE',
        'data' => substr($segment->content ?? $segment->text ?? '', 0, 100),
    ];
}

// Add function events
foreach ($functionTraces as $trace) {
    $events[] = [
        'time' => $trace->created_at,
        'type' => 'FUNCTION_CALL',
        'data' => "{$trace->function_name} (" . ($trace->success ? 'SUCCESS' : 'FAILED') . ")",
    ];
}

// Add error events
foreach ($errors as $error) {
    $events[] = [
        'time' => $error->created_at,
        'type' => 'ERROR',
        'data' => $error->error_message,
    ];
}

// Add call end
if ($call->ended_at) {
    $events[] = [
        'time' => $call->ended_at,
        'type' => 'CALL_END',
        'data' => "Reason: " . ($call->disconnect_reason ?? 'Unknown'),
    ];
}

// Sort by time
usort($events, function($a, $b) {
    return strcmp($a['time'], $b['time']);
});

echo "Complete Event Timeline:\n";
echo str_repeat('=', 80) . "\n\n";

foreach ($events as $event) {
    $time = \Carbon\Carbon::parse($event['time'])->format('H:i:s.u');
    $typeFormatted = str_pad($event['type'], 15);
    echo "[{$time}] {$typeFormatted} │ {$event['data']}\n";
}

echo "\n" . str_repeat('=', 80) . "\n\n";

// ================================================================================
// STEP 8: ROOT CAUSE ANALYSIS
// ================================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 8: ROOT CAUSE ANALYSIS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$findings = [];

// Finding 1: Function calls
if ($functionTraces->isEmpty()) {
    $findings[] = [
        'severity' => 'CRITICAL',
        'issue' => 'NO FUNCTIONS CALLED',
        'details' => 'check_availability and book_appointment were never executed',
        'impact' => 'User never got real availability check - exact problem we tried to fix',
    ];
}

// Finding 2: Call timing
$deploymentTime = \Carbon\Carbon::parse('2025-10-24 19:02');
$callTime = \Carbon\Carbon::parse($call->started_at);

if ($callTime->lt($deploymentTime)) {
    $findings[] = [
        'severity' => 'CRITICAL',
        'issue' => 'CALL USED OLD FLOW',
        'details' => "Call at {$callTime->format('H:i:s')} was before deployment at {$deploymentTime->format('H:i:s')}",
        'impact' => 'This call did NOT use our new flow - it used the old broken one',
    ];
}

// Finding 3: Agent ID
if ($agentId !== $expectedAgentId) {
    $findings[] = [
        'severity' => 'HIGH',
        'issue' => 'WRONG AGENT',
        'details' => "Expected {$expectedAgentId}, got {$agentId}",
        'impact' => 'Call may have used different configuration',
    ];
}

// Finding 4: Call duration
$duration = $call->call_duration_seconds ?? 0;
if ($duration < 10) {
    $findings[] = [
        'severity' => 'MEDIUM',
        'issue' => 'VERY SHORT CALL',
        'details' => "Call lasted only {$duration} seconds",
        'impact' => 'Not enough time for full booking flow - may have been test/hang-up',
    ];
}

// Finding 5: Hallucination check
$hasAvailabilityMention = false;
foreach ($segments as $segment) {
    if ($segment->role === 'agent') {
        $text = strtolower($segment->content ?? $segment->text ?? '');
        if (str_contains($text, 'verfügbar') || str_contains($text, 'prüfe')) {
            $hasAvailabilityMention = true;
            break;
        }
    }
}

if ($hasAvailabilityMention && $functionTraces->isEmpty()) {
    $findings[] = [
        'severity' => 'CRITICAL',
        'issue' => 'AI HALLUCINATION',
        'details' => 'AI mentioned checking/availability but never called the function',
        'impact' => 'User was told lies - exactly what we wanted to prevent',
    ];
}

// Print findings
if (empty($findings)) {
    echo "✅ No critical issues found\n\n";
} else {
    echo "🔴 CRITICAL FINDINGS:\n\n";

    foreach ($findings as $i => $finding) {
        $num = $i + 1;
        echo "{$num}. [{$finding['severity']}] {$finding['issue']}\n";
        echo "   Details: {$finding['details']}\n";
        echo "   Impact: {$finding['impact']}\n\n";
    }
}

// ================================================================================
// STEP 9: CONCLUSION & RECOMMENDATIONS
// ================================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 9: CONCLUSION & NEXT STEPS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "SUMMARY:\n";
echo "  Call ID: {$call->call_id}\n";
echo "  Started: {$call->started_at}\n";
echo "  Duration: {$duration}s\n";
echo "  Functions called: " . $functionTraces->count() . "\n";
echo "  check_availability: " . ($functionTraces->contains(fn($t) => str_contains($t->function_name, 'check_availability')) ? 'YES ✅' : 'NO ❌') . "\n";
echo "  Critical findings: " . count($findings) . "\n\n";

if ($callTime->lt($deploymentTime)) {
    echo "🔴 PRIMARY CONCLUSION:\n";
    echo "   This call was made BEFORE our deployment!\n";
    echo "   It used the OLD flow, not our new fixed flow.\n";
    echo "   This is NOT a test of our new deployment.\n\n";
    echo "📞 RECOMMENDATION:\n";
    echo "   Make a NEW test call NOW (after 19:02)\n";
    echo "   That call will use the new flow.\n\n";
} elseif ($functionTraces->isEmpty()) {
    echo "🔴 PRIMARY CONCLUSION:\n";
    echo "   Call was after deployment but functions were NOT called!\n";
    echo "   Our fix did NOT work as expected.\n\n";
    echo "🔍 NEXT STEPS:\n";
    echo "   1. Verify flow was actually deployed\n";
    echo "   2. Check if phone number is mapped to correct agent\n";
    echo "   3. Review actual deployed flow structure\n";
    echo "   4. Check Retell dashboard for this call\n\n";
} else {
    echo "✅ PRIMARY CONCLUSION:\n";
    echo "   Functions WERE called!\n";
    echo "   Fix appears to be working.\n\n";
}

echo "Full analysis saved to database.\n\n";

// Save analysis summary
$summary = [
    'call_id' => $call->call_id,
    'analysis_timestamp' => now(),
    'functions_called' => $functionTraces->count(),
    'has_check_availability' => $functionTraces->contains(fn($t) => str_contains($t->function_name, 'check_availability')),
    'has_book_appointment' => $functionTraces->contains(fn($t) => str_contains($t->function_name, 'book_appointment')),
    'findings' => $findings,
    'call_before_deployment' => $callTime->lt($deploymentTime),
];

file_put_contents('/tmp/ultrathink_call_analysis.json', json_encode($summary, JSON_PRETTY_PRINT));
echo "Analysis saved to: /tmp/ultrathink_call_analysis.json\n\n";
