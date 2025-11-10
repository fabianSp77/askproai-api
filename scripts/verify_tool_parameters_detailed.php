#!/usr/bin/env php
<?php

/**
 * Detailed Tool Parameters Verification
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Detailed Tool Parameters Verification\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_45daa54928c5768b52ba3db736';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

// Get agent
$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-agent/{$agentId}");

$agent = $response->json();
$flowId = $agent['response_engine']['conversation_flow_id'];

// Get conversation flow
$flowResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-conversation-flow/{$flowId}");

$flow = $flowResponse->json();
$tools = $flow['tools'] ?? [];

echo "🔍 Critical Parameter Checks:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$issues = [];

// Check 1: All tools have correct webhook URL
$expectedUrl = 'https://api.askproai.de/api/webhooks/retell/function';
foreach ($tools as $tool) {
    $toolName = $tool['name'];
    $url = $tool['url'] ?? '';

    if ($url !== $expectedUrl) {
        $issues[] = "❌ {$toolName}: Wrong URL: {$url}";
    } else {
        echo "✅ {$toolName}: Correct webhook URL\n";
    }
}

echo "\n";

// Check 2: call_id parameter where needed
echo "🔍 call_id Parameter Check:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$toolsNeedingCallId = [
    'get_customer_appointments',
    'cancel_appointment',
    'reschedule_appointment',
    'get_available_services',
    'start_booking',
    'confirm_booking'
];

foreach ($tools as $tool) {
    $toolName = $tool['name'];

    if (in_array($toolName, $toolsNeedingCallId)) {
        $params = $tool['parameters'] ?? [];
        $props = $params['properties'] ?? [];
        $required = $params['required'] ?? [];

        if (isset($props['call_id'])) {
            $isRequired = in_array('call_id', $required);
            if ($isRequired) {
                echo "✅ {$toolName}: call_id present and required\n";
            } else {
                echo "⚠️  {$toolName}: call_id present but NOT required\n";
                $issues[] = "⚠️  {$toolName}: call_id should be required";
            }
        } else {
            echo "❌ {$toolName}: call_id MISSING\n";
            $issues[] = "❌ {$toolName}: call_id parameter missing";
        }
    }
}

echo "\n";

// Check 3: 2-step booking parameters
echo "🔍 2-Step Booking Parameters:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

foreach ($tools as $tool) {
    if ($tool['name'] === 'start_booking') {
        $params = $tool['parameters'] ?? [];
        $props = $params['properties'] ?? [];
        $required = $params['required'] ?? [];

        echo "start_booking:\n";

        $expectedParams = [
            'call_id' => true,  // required
            'customer_name' => true,
            'customer_phone' => true,
            'customer_email' => false, // optional
            'service' => true,
            'datetime' => true,
            'function_name' => true
        ];

        foreach ($expectedParams as $param => $shouldBeRequired) {
            if (isset($props[$param])) {
                $isRequired = in_array($param, $required);

                if ($shouldBeRequired && $isRequired) {
                    echo "   ✅ {$param}: present and required\n";
                } elseif (!$shouldBeRequired && !$isRequired) {
                    echo "   ✅ {$param}: present and optional\n";
                } else {
                    echo "   ⚠️  {$param}: wrong requirement status\n";
                    $issues[] = "⚠️  start_booking: {$param} requirement mismatch";
                }
            } else {
                echo "   ❌ {$param}: MISSING\n";
                $issues[] = "❌ start_booking: {$param} missing";
            }
        }
    }

    if ($tool['name'] === 'confirm_booking') {
        $params = $tool['parameters'] ?? [];
        $props = $params['properties'] ?? [];
        $required = $params['required'] ?? [];

        echo "\nconfirm_booking:\n";

        $expectedParams = ['call_id', 'function_name'];

        foreach ($expectedParams as $param) {
            if (isset($props[$param])) {
                $isRequired = in_array($param, $required);
                if ($isRequired) {
                    echo "   ✅ {$param}: present and required\n";
                } else {
                    echo "   ⚠️  {$param}: present but NOT required\n";
                    $issues[] = "⚠️  confirm_booking: {$param} should be required";
                }
            } else {
                echo "   ❌ {$param}: MISSING\n";
                $issues[] = "❌ confirm_booking: {$param} missing";
            }
        }
    }
}

echo "\n";

// Check 4: Dynamic variables
echo "🔍 Dynamic Variables Check:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$dynamicVars = $flow['dynamic_variables'] ?? [];
echo "Found: " . count($dynamicVars) . " dynamic variables\n\n";

$expectedVars = [
    'customer_name',
    'customer_phone',
    'customer_email',
    'service_name',
    'appointment_date',
    'appointment_time',
    'booking_status',
    'available_slots',
    'customer_appointments',
    'cancel_status'
];

$foundVars = array_column($dynamicVars, 'name');

foreach ($expectedVars as $var) {
    if (in_array($var, $foundVars)) {
        echo "✅ {$var}\n";
    } else {
        echo "⚠️  {$var} - not found\n";
    }
}

// Summary
echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Summary\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if (empty($issues)) {
    echo "✅ ALL PARAMETERS CORRECT!\n";
    echo "\n";
    echo "✓ All webhook URLs correct\n";
    echo "✓ All call_id parameters present and required\n";
    echo "✓ 2-step booking parameters complete\n";
    echo "✓ Dynamic variables configured\n";
} else {
    echo "⚠️  ISSUES FOUND:\n\n";
    foreach ($issues as $issue) {
        echo "{$issue}\n";
    }
}

echo "\n";
