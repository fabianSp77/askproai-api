<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RetellCallSession;
use App\Models\Call;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  Checking Latest Call Data                                  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Check latest call in calls table
$latestCall = Call::latest('start_timestamp')->first();

if ($latestCall) {
    echo "📞 Latest Call (calls table):\n";
    echo "   Call ID: {$latestCall->external_id}\n";
    echo "   Started: {$latestCall->start_timestamp}\n";
    echo "   Status: {$latestCall->call_status}\n";
    echo "   Customer: " . ($latestCall->customer ? $latestCall->customer->name : 'Unknown') . "\n\n";
}

// Check latest call session in monitoring table
$latestSession = RetellCallSession::with(['functionTraces', 'errors'])
    ->latest('started_at')
    ->first();

if ($latestSession) {
    echo "📊 Latest Call Session (monitoring):\n";
    echo "   Session ID: {$latestSession->id}\n";
    echo "   Call ID: {$latestSession->call_id}\n";
    echo "   Started: {$latestSession->started_at}\n";
    echo "   Status: {$latestSession->call_status}\n";
    echo "   Duration: " . ($latestSession->getDurationSeconds() ?? 'In progress') . "\n";
    echo "   Function Calls: {$latestSession->function_call_count}\n";
    echo "   Errors: {$latestSession->error_count}\n\n";

    if ($latestSession->functionTraces->count() > 0) {
        echo "   🔧 Function Traces:\n";
        foreach ($latestSession->functionTraces->sortBy('execution_sequence') as $trace) {
            $status = $trace->status === 'success' ? '✅' : '❌';
            echo "      {$status} #{$trace->execution_sequence} {$trace->function_name} ";
            echo "({$trace->duration_ms}ms) - {$trace->status}\n";
        }
        echo "\n";
    }

    if ($latestSession->errors->count() > 0) {
        echo "   ⚠️  Errors:\n";
        foreach ($latestSession->errors as $error) {
            echo "      ❌ [{$error->severity}] {$error->error_code}: {$error->error_message}\n";
        }
        echo "\n";
    }

    echo "🔗 View in Filament:\n";
    echo "   https://api.askproai.de/admin/retell-call-sessions/{$latestSession->id}\n\n";
} else {
    echo "⚠️  No call sessions found in monitoring table yet.\n\n";
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  Ready to check!                                             ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
