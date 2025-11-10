<?php
/**
 * Analyze specific call with transcript flow
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;

$callId = $argv[1] ?? 'call_2f1253386d1eabf76cec90eb2cf';

echo "=== CALL FLOW ANALYSIS ===\n\n";
echo "Analyzing: {$callId}\n\n";

$call = Call::where('retell_call_id', $callId)->first();

if (!$call) {
    die("❌ Call not found!\n");
}

// Get transcript from raw data
$raw = is_string($call->raw) ? json_decode($call->raw, true) : $call->raw;
$transcript = $raw['transcript'] ?? '';

echo "=== TRANSCRIPT ===\n\n";
echo $transcript . "\n\n";

echo "=== FLOW ANALYSIS ===\n\n";

// Extract conversation flow from transcript_with_tool_calls
if (isset($raw['transcript_with_tool_calls'])) {
    $events = $raw['transcript_with_tool_calls'];

    $step = 1;
    foreach ($events as $event) {
        $role = $event['role'] ?? '';

        if ($role === 'node_transition') {
            echo "[$step] NODE TRANSITION\n";
            echo "    From: {$event['former_node_name']}\n";
            echo "    To: {$event['new_node_name']}\n";
            echo "    Time: {$event['time_sec']}s\n\n";
            $step++;
        }

        if ($role === 'tool_call_invocation') {
            echo "[$step] TOOL CALL\n";
            echo "    Tool: {$event['name']}\n";
            echo "    Arguments: {$event['arguments']}\n";
            echo "    Time: {$event['time_sec']}s\n\n";
            $step++;
        }

        if ($role === 'tool_call_result') {
            echo "[$step] TOOL RESULT\n";
            echo "    Success: " . ($event['successful'] ?? 'N/A') . "\n";
            $content = isset($event['content']) ? substr($event['content'], 0, 200) : 'N/A';
            echo "    Content: {$content}...\n";
            echo "    Time: {$event['time_sec']}s\n\n";
            $step++;
        }

        if ($role === 'agent') {
            $content = $event['content'] ?? '';
            if (strlen($content) > 100) {
                $content = substr($content, 0, 100) . '...';
            }
            echo "[$step] AGENT SAYS\n";
            echo "    \"{$content}\"\n";
            echo "    Time: {$event['words'][0]['start']}s\n\n";
            $step++;
        }

        if ($role === 'user') {
            $content = $event['content'] ?? '';
            echo "[$step] USER SAYS\n";
            echo "    \"{$content}\"\n";
            echo "    Time: {$event['words'][0]['start']}s\n\n";
            $step++;
        }
    }
}

echo "=== END ANALYSIS ===\n";
