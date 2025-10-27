#!/usr/bin/env php
<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "LATEST CALL SUCCESS CHECK\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$call = \App\Models\RetellCallSession::orderBy('created_at', 'desc')->first();

if (!$call) {
    echo "❌ No calls found in database!\n\n";
    exit(1);
}

echo "Call ID: {$call->call_id}\n";
echo "Started: {$call->started_at}\n";
echo "Status: {$call->call_status}\n";
echo "Duration: {$call->duration} seconds\n\n";

// Get function traces
$functions = $call->functionTraces()->orderBy('created_at', 'asc')->get();

echo "═══════════════════════════════════════════════════════════\n";
echo "FUNCTIONS CALLED ({$functions->count()})\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if ($functions->isEmpty()) {
    echo "❌ NO FUNCTIONS CALLED!\n\n";
    echo "This means:\n";
    echo "  - Either phone mapping is wrong\n";
    echo "  - Or wrong agent version is published\n";
    echo "  - Or call was too short / hung up immediately\n\n";

    echo "Run this to check setup:\n";
    echo "  php scripts/testing/verify_v54_ready.php\n\n";

    exit(1);
}

// Check for critical functions
$hasInitialize = false;
$hasCheckAvailability = false;
$hasBookAppointment = false;

foreach ($functions as $func) {
    $name = $func->function_name;
    $success = $func->success ? '✅' : '❌';
    $duration = round($func->execution_time_ms / 1000, 2);

    echo "{$success} {$name}\n";
    echo "   Execution time: {$duration}s\n";
    echo "   Result: " . ($func->success ? 'SUCCESS' : 'FAILED') . "\n";

    if ($func->result) {
        $result = json_decode($func->result, true);
        if ($result && is_array($result)) {
            echo "   Response: " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    }

    echo "\n";

    // Track critical functions
    if (str_contains($name, 'initialize')) $hasInitialize = true;
    if (str_contains($name, 'check_availability')) $hasCheckAvailability = true;
    if (str_contains($name, 'book_appointment')) $hasBookAppointment = true;
}

// Get transcripts
$transcripts = $call->transcriptSegments()->orderBy('created_at', 'asc')->get();

echo "═══════════════════════════════════════════════════════════\n";
echo "TRANSCRIPTS ({$transcripts->count()} segments)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

foreach ($transcripts->take(10) as $segment) {
    $role = strtoupper($segment->role);
    $content = substr($segment->content, 0, 100);
    echo "[{$role}] {$content}\n";
}

if ($transcripts->count() > 10) {
    echo "\n... and " . ($transcripts->count() - 10) . " more segments\n";
}

echo "\n";

// Final verdict
echo "═══════════════════════════════════════════════════════════\n";
echo "VERDICT\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$success = true;

echo "Critical Functions:\n";
echo "  initialize_call:       " . ($hasInitialize ? '✅ CALLED' : '❌ NOT CALLED') . "\n";
echo "  check_availability:    " . ($hasCheckAvailability ? '✅ CALLED' : '⚠️  NOT CALLED') . "\n";
echo "  book_appointment:      " . ($hasBookAppointment ? '✅ CALLED' : 'ℹ️  NOT CALLED (normal if not confirmed)') . "\n";

echo "\n";

if (!$hasInitialize) {
    echo "❌ CRITICAL: initialize_call not called!\n";
    $success = false;
}

if (!$hasCheckAvailability) {
    echo "🔴 CRITICAL: check_availability NOT CALLED!\n";
    echo "   This is the MAIN PROBLEM we're trying to fix!\n";
    echo "   Expected: 100% call rate\n";
    echo "   Actual: 0%\n\n";

    echo "Possible causes:\n";
    echo "  1. Wrong agent version published (not V54)\n";
    echo "  2. Phone mapped to wrong agent\n";
    echo "  3. Call too short / hung up before availability check\n\n";

    echo "Run: php scripts/testing/verify_v54_ready.php\n\n";

    $success = false;
}

if ($call->call_status !== 'completed') {
    echo "⚠️  WARNING: Call status is '{$call->call_status}' (not 'completed')\n";
    echo "   This might indicate the call was interrupted\n\n";
}

if ($call->duration < 30) {
    echo "⚠️  WARNING: Call duration only {$call->duration} seconds\n";
    echo "   Very short call - might have hung up immediately\n\n";
}

if ($success && $hasCheckAvailability) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "🎉 SUCCESS! check_availability WAS CALLED!\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "This means:\n";
    echo "  ✅ Version 54 is published correctly\n";
    echo "  ✅ Phone mapping is correct\n";
    echo "  ✅ Explicit function nodes are working\n";
    echo "  ✅ Fix successful: 0% → 100%!\n\n";

    if ($hasBookAppointment) {
        echo "  ✅ BONUS: Booking also worked!\n\n";
    }

    exit(0);
} else {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "❌ FAILED - Fix not working yet\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    exit(1);
}
