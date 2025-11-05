#!/usr/bin/env php
<?php

/**
 * Analyze Recent Calls from Retell AI API
 *
 * Usage: php scripts/analyze_recent_calls.php [phone_number]
 * Example: php scripts/analyze_recent_calls.php +4930123456
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   RETELL AI - RECENT CALLS ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$retellApiKey = env('RETELL_TOKEN', 'key_6ff998ba48e842092e04a5455d19');
$retellBaseUrl = env('RETELL_BASE', 'https://api.retellai.com');

// Get filter from command line arguments
$filterPhone = $argv[1] ?? null;

echo "🔑 API Key: " . substr($retellApiKey, 0, 8) . "...\n";
echo "🌐 Base URL: {$retellBaseUrl}\n";
if ($filterPhone) {
    echo "📞 Filter: {$filterPhone}\n";
}
echo "\n";

// Step 1: List all phone numbers
echo "📋 STEP 1: Listing all phone numbers...\n\n";

try {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $retellApiKey,
        'Content-Type' => 'application/json',
    ])->get("{$retellBaseUrl}/v2/list-phone-numbers");

    if ($response->successful()) {
        $phoneNumbers = $response->json();

        echo "Found " . count($phoneNumbers) . " phone number(s):\n\n";

        foreach ($phoneNumbers as $phone) {
            echo "📞 Number: {$phone['phone_number']}\n";
            if (isset($phone['agent_id'])) {
                echo "   Agent: {$phone['agent_id']}\n";
            }
            if (isset($phone['nickname'])) {
                echo "   Nickname: {$phone['nickname']}\n";
            }
            echo "\n";
        }
    } else {
        echo "❌ Failed to list phone numbers: " . $response->status() . "\n";
        echo $response->body() . "\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: List recent calls
echo "\n📞 STEP 2: Fetching recent calls...\n\n";

try {
    $url = "{$retellBaseUrl}/v2/list-calls";
    $params = [
        'limit' => 20, // Get last 20 calls
        'sort_order' => 'descending',
    ];

    if ($filterPhone) {
        // Retell API might use different parameter names for filtering
        // Check documentation for exact parameter name
        $params['filter_criteria'] = [
            'phone_number' => $filterPhone
        ];
    }

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $retellApiKey,
        'Content-Type' => 'application/json',
    ])->get($url, $params);

    if ($response->successful()) {
        $data = $response->json();
        $calls = $data['calls'] ?? $data ?? [];

        echo "Found " . count($calls) . " recent call(s):\n\n";

        foreach ($calls as $index => $call) {
            echo "═══════════════════════════════════════════════════════════════\n";
            echo "CALL #" . ($index + 1) . ": {$call['call_id']}\n";
            echo "═══════════════════════════════════════════════════════════════\n\n";

            // Basic info
            echo "📊 Basic Information:\n";
            echo "   Call ID: {$call['call_id']}\n";
            echo "   Agent ID: " . ($call['agent_id'] ?? 'N/A') . "\n";
            echo "   From: " . ($call['from_number'] ?? 'N/A') . "\n";
            echo "   To: " . ($call['to_number'] ?? 'N/A') . "\n";
            echo "   Status: " . ($call['call_status'] ?? 'N/A') . "\n";
            echo "   Duration: " . ($call['call_length'] ?? 'N/A') . " seconds\n";

            // Timestamps
            if (isset($call['start_timestamp'])) {
                $startTime = \Carbon\Carbon::createFromTimestampMs($call['start_timestamp']);
                echo "   Start: {$startTime->format('Y-m-d H:i:s')}\n";
            }
            if (isset($call['end_timestamp'])) {
                $endTime = \Carbon\Carbon::createFromTimestampMs($call['end_timestamp']);
                echo "   End: {$endTime->format('Y-m-d H:i:s')}\n";
            }

            // Disconnection reason
            if (isset($call['disconnection_reason'])) {
                echo "   Disconnection: {$call['disconnection_reason']}\n";
            }

            echo "\n";

            // Analysis
            if (isset($call['call_analysis'])) {
                echo "📈 Analysis:\n";
                $analysis = $call['call_analysis'];

                if (isset($analysis['user_sentiment'])) {
                    echo "   Sentiment: {$analysis['user_sentiment']}\n";
                }
                if (isset($analysis['call_successful'])) {
                    echo "   Successful: " . ($analysis['call_successful'] ? 'Yes' : 'No') . "\n";
                }
                if (isset($analysis['summary'])) {
                    echo "   Summary: {$analysis['summary']}\n";
                }

                echo "\n";
            }

            // Transcript (shortened)
            if (isset($call['transcript'])) {
                echo "💬 Transcript Preview:\n";
                $transcript = $call['transcript'];
                if (is_string($transcript)) {
                    echo "   " . substr($transcript, 0, 200) . "...\n";
                } else {
                    echo "   " . json_encode($transcript, JSON_PRETTY_PRINT) . "\n";
                }
                echo "\n";
            }

            echo "\n";
        }

        // Now fetch detailed info for the last 4 calls
        echo "\n\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "   DETAILED ANALYSIS OF LAST 4 CALLS\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        $callsToAnalyze = array_slice($calls, 0, 4);

        foreach ($callsToAnalyze as $index => $call) {
            echo "\n\n";
            echo "╔══════════════════════════════════════════════════════════════╗\n";
            echo "║  DETAILED CALL #" . ($index + 1) . ": {$call['call_id']}\n";
            echo "╚══════════════════════════════════════════════════════════════╝\n\n";

            // Fetch full call details
            $detailResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $retellApiKey,
                'Content-Type' => 'application/json',
            ])->get("{$retellBaseUrl}/v2/get-call/{$call['call_id']}");

            if ($detailResponse->successful()) {
                $fullCall = $detailResponse->json();

                // Full transcript
                if (isset($fullCall['transcript'])) {
                    echo "📝 FULL TRANSCRIPT:\n";
                    echo "─────────────────────────────────────────────────────────\n";

                    $transcript = $fullCall['transcript'];
                    if (is_string($transcript)) {
                        echo $transcript . "\n";
                    } elseif (is_array($transcript)) {
                        foreach ($transcript as $turn) {
                            $speaker = strtoupper($turn['role'] ?? 'unknown');
                            $content = $turn['content'] ?? '';
                            $timestamp = $turn['timestamp'] ?? '';

                            echo "\n[{$speaker}";
                            if ($timestamp) {
                                echo " @ {$timestamp}s";
                            }
                            echo "]:\n{$content}\n";
                        }
                    }

                    echo "─────────────────────────────────────────────────────────\n\n";
                }

                // Function calls / tool calls
                if (isset($fullCall['tool_calls']) || isset($fullCall['function_calls'])) {
                    $toolCalls = $fullCall['tool_calls'] ?? $fullCall['function_calls'] ?? [];

                    if (!empty($toolCalls)) {
                        echo "🔧 FUNCTION CALLS:\n";
                        echo "─────────────────────────────────────────────────────────\n";

                        foreach ($toolCalls as $idx => $toolCall) {
                            echo "\n[Function Call #" . ($idx + 1) . "]\n";
                            echo "Name: " . ($toolCall['name'] ?? $toolCall['function_name'] ?? 'N/A') . "\n";

                            if (isset($toolCall['arguments']) || isset($toolCall['parameters'])) {
                                $args = $toolCall['arguments'] ?? $toolCall['parameters'] ?? [];
                                echo "Arguments:\n";
                                echo json_encode($args, JSON_PRETTY_PRINT) . "\n";
                            }

                            if (isset($toolCall['result']) || isset($toolCall['response'])) {
                                $result = $toolCall['result'] ?? $toolCall['response'] ?? null;
                                echo "Result:\n";
                                echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
                            }
                        }

                        echo "─────────────────────────────────────────────────────────\n\n";
                    }
                }

                // Recording URL
                if (isset($fullCall['recording_url'])) {
                    echo "🎵 Recording URL:\n   {$fullCall['recording_url']}\n\n";
                }

                // Analysis
                if (isset($fullCall['call_analysis'])) {
                    echo "📊 CALL ANALYSIS:\n";
                    echo "─────────────────────────────────────────────────────────\n";
                    $analysis = $fullCall['call_analysis'];
                    echo json_encode($analysis, JSON_PRETTY_PRINT) . "\n";
                    echo "─────────────────────────────────────────────────────────\n\n";
                }

                // Full raw data (for debugging)
                echo "🔍 RAW CALL DATA:\n";
                echo "─────────────────────────────────────────────────────────\n";
                echo json_encode($fullCall, JSON_PRETTY_PRINT) . "\n";
                echo "─────────────────────────────────────────────────────────\n";

            } else {
                echo "❌ Failed to fetch details: " . $detailResponse->status() . "\n";
            }

            // Rate limiting
            usleep(500000); // 0.5 second delay
        }

    } else {
        echo "❌ Failed to list calls: " . $response->status() . "\n";
        echo $response->body() . "\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   ANALYSIS COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
