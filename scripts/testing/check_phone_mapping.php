#!/usr/bin/env php
<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  PHONE NUMBER TO AGENT MAPPING CHECK                    ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

if (!$token) {
    echo "❌ RETELL_TOKEN not set\n\n";
    exit(1);
}

// Get all phone numbers
try {
    $response = Http::withHeaders([
        'Authorization' => "Bearer $token",
    ])->get('https://api.retellai.com/list-phone-numbers');

    if ($response->successful()) {
        $phoneNumbers = $response->json();

        echo "Total phone numbers: " . count($phoneNumbers) . "\n\n";

        $ourAgentPhones = [];

        foreach ($phoneNumbers as $phone) {
            $number = $phone['phone_number'] ?? 'N/A';
            $assignedAgent = $phone['agent_id'] ?? 'NONE';
            $nickname = $phone['nickname'] ?? '';

            echo "📞 Phone: $number\n";
            if ($nickname) {
                echo "   Nickname: $nickname\n";
            }
            echo "   Agent ID: $assignedAgent\n";

            if ($assignedAgent === $agentId) {
                echo "   ✅ MAPPED TO FRISEUR 1 AGENT (CORRECT!)\n";
                $ourAgentPhones[] = $number;
            } elseif ($assignedAgent === 'NONE' || !$assignedAgent) {
                echo "   ❌ NO AGENT ASSIGNED!\n";
            } else {
                echo "   ⚠️  MAPPED TO DIFFERENT AGENT ($assignedAgent)\n";
            }
            echo "\n";
        }

        // Summary
        echo str_repeat('=', 60) . "\n\n";

        if (empty($ourAgentPhones)) {
            echo "🔴 CRITICAL PROBLEM FOUND:\n";
            echo "   NO phone number is mapped to agent $agentId!\n\n";
            echo "This means:\n";
            echo "  - Test calls CANNOT reach our deployed flow\n";
            echo "  - Calls go to wrong agent or no agent at all\n";
            echo "  - Our fix cannot be tested!\n\n";
            echo "ACTION REQUIRED:\n";
            echo "  1. Go to: https://dashboard.retellai.com/phone-numbers\n";
            echo "  2. Assign a phone number to agent: $agentId\n";
            echo "  3. Then make test call to that number\n\n";
        } else {
            echo "✅ PHONE MAPPING OK\n";
            echo "   " . count($ourAgentPhones) . " phone number(s) mapped to Friseur 1 agent:\n";
            foreach ($ourAgentPhones as $num) {
                echo "   → $num\n";
            }
            echo "\n";
            echo "You can make test calls to these numbers.\n\n";
        }

    } else {
        echo "❌ Error fetching phone numbers: " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n\n";
}
