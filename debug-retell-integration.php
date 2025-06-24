#!/usr/bin/env php
<?php

/**
 * Retell Integration Debug Script
 * This script helps analyze and debug the Retell.ai integration
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\WebhookEvent;
use App\Models\Company;
use App\Models\PhoneNumber;
use App\Models\Branch;
use App\Services\MCP\RetellMCPServer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n=== RETELL INTEGRATION DEBUG TOOL ===\n\n";

// 1. Check recent webhook events
echo "1. Recent Retell Webhook Events:\n";
echo str_repeat('-', 80) . "\n";

$recentWebhooks = WebhookEvent::where('provider', 'retell')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($recentWebhooks->isEmpty()) {
    echo "❌ No Retell webhook events found!\n";
} else {
    foreach ($recentWebhooks as $webhook) {
        echo sprintf(
            "📅 %s | Event: %s | Status: %s | ID: %s\n",
            $webhook->created_at->format('Y-m-d H:i:s'),
            $webhook->event_type,
            $webhook->status,
            $webhook->event_id
        );
        
        // Check for appointment data in payload
        if (isset($webhook->payload['call']['retell_llm_dynamic_variables'])) {
            $dynamicVars = $webhook->payload['call']['retell_llm_dynamic_variables'];
            if (!empty($dynamicVars)) {
                echo "   ✓ Has dynamic variables: " . implode(', ', array_keys($dynamicVars)) . "\n";
            }
        }
    }
}

echo "\n";

// 2. Check recent calls
echo "2. Recent Calls:\n";
echo str_repeat('-', 80) . "\n";

$recentCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($recentCalls->isEmpty()) {
    echo "❌ No calls found!\n";
} else {
    foreach ($recentCalls as $call) {
        echo sprintf(
            "📞 %s | From: %s | Duration: %ds | Has Appointment: %s\n",
            $call->created_at->format('Y-m-d H:i:s'),
            $call->from_number ?? 'Unknown',
            $call->duration_sec ?? 0,
            $call->appointment_id ? '✓' : '✗'
        );
        
        // Check for dynamic variables
        if ($call->retell_llm_dynamic_variables) {
            $vars = is_string($call->retell_llm_dynamic_variables) 
                ? json_decode($call->retell_llm_dynamic_variables, true) 
                : $call->retell_llm_dynamic_variables;
                
            if (!empty($vars)) {
                echo "   ✓ Dynamic vars: " . implode(', ', array_keys($vars)) . "\n";
            }
        }
        
        // Check metadata for appointment intent
        if ($call->metadata && isset($call->metadata['appointment_intent_detected'])) {
            echo "   ⚠️  Appointment intent detected but no booking!\n";
        }
    }
}

echo "\n";

// 3. Check phone number configuration
echo "3. Phone Number Configuration:\n";
echo str_repeat('-', 80) . "\n";

$phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('is_active', true)
    ->with(['branch', 'company'])
    ->limit(5)
    ->get();

if ($phoneNumbers->isEmpty()) {
    echo "❌ No active phone numbers configured!\n";
} else {
    foreach ($phoneNumbers as $phone) {
        echo sprintf(
            "☎️  %s | Branch: %s | Agent: %s\n",
            $phone->number,
            $phone->branch ? $phone->branch->name : 'None',
            $phone->retell_agent_id ?? 'Not set'
        );
    }
}

echo "\n";

// 4. Test Retell Connection
echo "4. Testing Retell Connection:\n";
echo str_repeat('-', 80) . "\n";

$company = Company::first();
if ($company) {
    $mcp = new RetellMCPServer();
    $result = $mcp->testConnection(['company_id' => $company->id]);
    
    if ($result['connected'] ?? false) {
        echo "✅ Retell API Connection: OK\n";
        echo "   Agent Count: " . ($result['agent_count'] ?? 0) . "\n";
        if ($result['configured_agent'] ?? null) {
            echo "   Configured Agent: " . $result['configured_agent']['agent_name'] . "\n";
        }
    } else {
        echo "❌ Retell API Connection: FAILED\n";
        echo "   Error: " . ($result['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "❌ No company found!\n";
}

echo "\n";

// 5. Analyze webhook failures
echo "5. Failed Webhook Analysis:\n";
echo str_repeat('-', 80) . "\n";

$failedWebhooks = WebhookEvent::where('provider', 'retell')
    ->where('status', 'failed')
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();

if ($failedWebhooks->isEmpty()) {
    echo "✅ No failed webhooks found.\n";
} else {
    foreach ($failedWebhooks as $webhook) {
        echo sprintf(
            "❌ %s | Event: %s | Error: %s\n",
            $webhook->created_at->format('Y-m-d H:i:s'),
            $webhook->event_type,
            substr($webhook->error_message ?? 'No error message', 0, 100)
        );
    }
}

echo "\n";

// 6. Check for calls with appointment intent but no booking
echo "6. Calls with Appointment Intent but No Booking:\n";
echo str_repeat('-', 80) . "\n";

$callsWithIntent = DB::table('calls')
    ->whereNull('appointment_id')
    ->where(function($query) {
        $query->whereRaw("JSON_EXTRACT(metadata, '$.appointment_intent_detected') = true")
              ->orWhere('transcript', 'LIKE', '%termin%')
              ->orWhere('transcript', 'LIKE', '%buchen%');
    })
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($callsWithIntent->isEmpty()) {
    echo "✅ No calls found with missed appointment intent.\n";
} else {
    echo "⚠️  Found " . count($callsWithIntent) . " calls that might need appointments:\n";
    foreach ($callsWithIntent as $call) {
        echo sprintf(
            "   - Call ID: %s | Date: %s | From: %s\n",
            $call->id,
            $call->created_at,
            $call->from_number ?? 'Unknown'
        );
    }
}

echo "\n";

// 7. Import recent calls from Retell
echo "7. Import Recent Calls from Retell API:\n";
echo str_repeat('-', 80) . "\n";

$confirm = readline("Do you want to import recent calls from Retell? (y/N): ");
if (strtolower($confirm) === 'y') {
    if ($company) {
        $mcp = new RetellMCPServer();
        $importResult = $mcp->importRecentCalls(['company_id' => $company->id]);
        
        if (isset($importResult['error'])) {
            echo "❌ Import failed: " . $importResult['error'] . "\n";
        } else {
            echo "✅ Import completed:\n";
            echo "   - Imported: " . ($importResult['imported'] ?? 0) . "\n";
            echo "   - Skipped: " . ($importResult['skipped'] ?? 0) . "\n";
            echo "   - Errors: " . count($importResult['errors'] ?? []) . "\n";
        }
    }
} else {
    echo "Skipped import.\n";
}

echo "\n";

// 8. Recommendations
echo "8. Recommendations:\n";
echo str_repeat('-', 80) . "\n";

$issues = [];

if ($recentWebhooks->isEmpty()) {
    $issues[] = "No webhooks received - Check Retell webhook configuration";
}

if ($recentCalls->isEmpty()) {
    $issues[] = "No calls in database - Run import or check webhook processing";
}

$callsWithoutAppointments = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNull('appointment_id')
    ->where('created_at', '>=', now()->subDays(7))
    ->count();

if ($callsWithoutAppointments > 10) {
    $issues[] = "High number of calls without appointments ($callsWithoutAppointments) - Check appointment booking logic";
}

$activePhones = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('is_active', true)
    ->whereNotNull('retell_agent_id')
    ->count();

if ($activePhones === 0) {
    $issues[] = "No phone numbers with Retell agents configured";
}

if (empty($issues)) {
    echo "✅ No critical issues detected.\n";
} else {
    echo "⚠️  Issues found:\n";
    foreach ($issues as $issue) {
        echo "   - $issue\n";
    }
}

echo "\n=== END OF DEBUG REPORT ===\n\n";