<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RetellCallSession;
use App\Models\RetellFunctionTrace;

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "  TESTANRUF ANALYSE\n";
echo "═══════════════════════════════════════════════════════\n\n";

$latest = RetellCallSession::latest()->first();

if (!$latest) {
    echo "❌ Keine Call Sessions gefunden\n\n";
    exit(1);
}

echo "📞 Call ID: " . $latest->call_id . "\n";
echo "   Status: " . $latest->call_status . "\n";
echo "   Started: " . $latest->started_at . "\n";

if ($latest->ended_at) {
    echo "   Ended: " . $latest->ended_at . "\n";
}

if ($latest->customer) {
    echo "   👤 Customer: " . $latest->customer->name . "\n";
} else {
    echo "   👤 Customer: (keine Daten)\n";
}

echo "\n";

$traces = RetellFunctionTrace::where('call_session_id', $latest->id)
    ->orderBy('created_at')
    ->get();

echo "🔧 Function Traces: " . $traces->count() . "\n";

if ($traces->count() > 0) {
    foreach ($traces as $trace) {
        $status = $trace->status == 'success' ? '✅' : '❌';
        echo "   " . $status . " " . $trace->function_name . " (" . $trace->status . ")\n";
        
        if ($trace->error_message) {
            echo "      Error: " . $trace->error_message . "\n";
        }
    }
} else {
    echo "   ⚠️  KEINE TRACES - Functions wurden nicht ausgeführt!\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════\n";

// Bewertung
if ($traces->count() >= 3 && $latest->call_status == 'ended') {
    echo "  ✅ ERFOLG! System funktioniert!\n";
} elseif ($traces->count() > 0) {
    echo "  ⚠️  TEILWEISE - Functions laufen, aber Call nicht beendet\n";
} else {
    echo "  ❌ FEHLER - Keine Functions ausgeführt\n";
}

echo "═══════════════════════════════════════════════════════\n\n";

// Check logs
echo "📋 Letzte Log-Einträge:\n\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20);
    
    foreach ($lastLines as $line) {
        if (strpos($line, 'getCallContext') !== false || 
            strpos($line, 'initialize_call') !== false ||
            strpos($line, 'Call context not found') !== false) {
            echo $line;
        }
    }
}

echo "\n";
