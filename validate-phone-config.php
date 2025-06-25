#!/usr/bin/env php
<?php

/**
 * Validate Phone Configuration for AskProAI
 * 
 * This script checks all critical components for phone-to-appointment booking
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PhoneNumber;
use App\Models\Branch;
use App\Models\Company;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

echo "\n=== AskProAI Phone Configuration Validator ===\n";
echo "Time: " . now()->format('Y-m-d H:i:s') . " (Europe/Berlin)\n\n";

// Color codes for output
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$reset = "\033[0m";

$errors = [];
$warnings = [];
$successes = [];

// 1. Check Phone Numbers
echo "1. Checking Phone Numbers Configuration...\n";
$phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('is_active', true)
    ->get();

if ($phoneNumbers->count() == 0) {
    $errors[] = "No active phone numbers configured!";
} else {
    foreach ($phoneNumbers as $phone) {
        echo "   Phone: {$phone->number}\n";
        
        // Check branch
        if (!$phone->branch_id) {
            $errors[] = "Phone {$phone->number} has no branch assigned!";
        } else {
            $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->find($phone->branch_id);
            if (!$branch) {
                $errors[] = "Phone {$phone->number} has invalid branch ID: {$phone->branch_id}";
            } else {
                echo "   → Branch: {$branch->name}\n";
                
                // Check Cal.com event type
                if (!$branch->calcom_event_type_id) {
                    $warnings[] = "Branch '{$branch->name}' has no Cal.com event type configured!";
                } else {
                    echo "   → Cal.com Event Type ID: {$branch->calcom_event_type_id}\n";
                    $successes[] = "Phone {$phone->number} → Branch '{$branch->name}' → Cal.com Event {$branch->calcom_event_type_id}";
                }
            }
        }
        
        // Check Retell agent
        if ($phone->retell_agent_id) {
            echo "   → Retell Agent ID: {$phone->retell_agent_id}\n";
        } else {
            $warnings[] = "Phone {$phone->number} has no Retell agent ID";
        }
        
        echo "\n";
    }
}

// 2. Check Retell Agents
echo "\n2. Checking Retell Agents...\n";
$agents = DB::table('retell_agents')
    ->where('is_active', true)
    ->get();

if ($agents->count() == 0) {
    $errors[] = "No active Retell agents found!";
} else {
    foreach ($agents as $agent) {
        echo "   Agent: {$agent->name}\n";
        echo "   → ID: {$agent->agent_id}\n";
        
        if ($agent->configuration) {
            $config = json_decode($agent->configuration, true);
            
            // Check webhook URL
            $webhookUrl = $config['webhook_url'] ?? null;
            if (!$webhookUrl) {
                $errors[] = "Agent '{$agent->name}' has no webhook URL!";
            } elseif (strpos($webhookUrl, 'api.askproai.de') === false) {
                $warnings[] = "Agent '{$agent->name}' webhook URL doesn't point to api.askproai.de: {$webhookUrl}";
            } else {
                echo "   → Webhook: {$green}✓{$reset} {$webhookUrl}\n";
            }
            
            // Check language
            $language = $config['language'] ?? null;
            echo "   → Language: " . ($language ?: "{$red}NOT SET{$reset}") . "\n";
            
            // Check for collect_appointment_data function
            $hasCollectFunction = false;
            $functionCount = 0;
            
            if (isset($config['llm_configuration']['general_tools'])) {
                $functionCount = count($config['llm_configuration']['general_tools']);
                foreach ($config['llm_configuration']['general_tools'] as $tool) {
                    if ($tool['name'] === 'collect_appointment_data') {
                        $hasCollectFunction = true;
                        // Check the URL
                        if (isset($tool['url'])) {
                            $expectedUrl = 'https://api.askproai.de/api/retell/collect-appointment';
                            if ($tool['url'] !== $expectedUrl) {
                                $warnings[] = "Agent '{$agent->name}' collect_appointment_data URL incorrect: {$tool['url']}";
                            }
                        }
                    }
                }
            } elseif (isset($config['general_tools'])) {
                // Check in root level too (older format)
                $functionCount = count($config['general_tools']);
                foreach ($config['general_tools'] as $tool) {
                    if ($tool['name'] === 'collect_appointment_data') {
                        $hasCollectFunction = true;
                    }
                }
            }
            
            echo "   → Functions: {$functionCount} total\n";
            echo "   → collect_appointment_data: " . ($hasCollectFunction ? "{$green}✓{$reset}" : "{$red}✗{$reset}") . "\n";
            
            if (!$hasCollectFunction) {
                $errors[] = "Agent '{$agent->name}' missing collect_appointment_data function!";
            }
        } else {
            $errors[] = "Agent '{$agent->name}' has no configuration!";
        }
        
        echo "\n";
    }
}

// 3. Check Services
echo "\n3. Checking System Services...\n";

// Check Redis
try {
    $redis = Cache::getRedis();
    $redis->ping();
    echo "   Redis: {$green}✓ Connected{$reset}\n";
    $successes[] = "Redis connection working";
} catch (\Exception $e) {
    $errors[] = "Redis connection failed: " . $e->getMessage();
    echo "   Redis: {$red}✗ Failed{$reset}\n";
}

// Check database
try {
    DB::connection()->getPdo();
    echo "   Database: {$green}✓ Connected{$reset}\n";
    $successes[] = "Database connection working";
} catch (\Exception $e) {
    $errors[] = "Database connection failed: " . $e->getMessage();
    echo "   Database: {$red}✗ Failed{$reset}\n";
}

// Check Cal.com
echo "\n4. Checking Cal.com Integration...\n";
try {
    $calcom = new CalcomV2Service();
    // Try to list event types as a connection test
    $eventTypes = $calcom->getEventTypes();
    if ($eventTypes !== null) {
        echo "   Cal.com API: {$green}✓ Connected{$reset}\n";
        $successes[] = "Cal.com API connected";
        
        if (is_array($eventTypes)) {
            $eventTypeCount = count($eventTypes);
            echo "   Event Types: {$eventTypeCount} available\n";
            
            // Check if our configured event types exist
            $configuredEventTypes = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->whereNotNull('calcom_event_type_id')
                ->pluck('calcom_event_type_id')
                ->unique()
                ->filter();
            
            $availableIds = collect($eventTypes)->pluck('id')->map(function($id) { return (string)$id; });
            foreach ($configuredEventTypes as $eventTypeId) {
                if (!$availableIds->contains((string)$eventTypeId)) {
                    $warnings[] = "Cal.com event type ID {$eventTypeId} not found in Cal.com!";
                } else {
                    $eventType = collect($eventTypes)->firstWhere('id', $eventTypeId);
                    if ($eventType) {
                        echo "   → Event Type {$eventTypeId}: " . ($eventType['title'] ?? 'Unknown') . "\n";
                    }
                }
            }
        }
    } else {
        $errors[] = "Cal.com API connection failed - no response";
        echo "   Cal.com: {$red}✗ Failed{$reset}\n";
    }
} catch (\Exception $e) {
    $errors[] = "Cal.com connection error: " . $e->getMessage();
    echo "   Cal.com: {$red}✗ Error{$reset}\n";
}

// 5. Check Webhook Endpoints
echo "\n5. Checking Webhook Endpoints...\n";

$endpoints = [
    'Collect Appointment' => 'https://api.askproai.de/api/retell/collect-appointment/test',
    'Zeitinfo (Time)' => 'https://api.askproai.de/api/zeitinfo?locale=de',
    'Test Webhook' => 'https://api.askproai.de/api/test/webhook'
];

foreach ($endpoints as $name => $url) {
    try {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            echo "   {$name}: {$green}✓ OK{$reset}\n";
            $successes[] = "Endpoint {$name} responding";
        } else {
            $warnings[] = "Endpoint {$name} returned HTTP {$httpCode}";
            echo "   {$name}: {$yellow}⚠ HTTP {$httpCode}{$reset}\n";
        }
    } catch (\Exception $e) {
        $errors[] = "Endpoint {$name} failed: " . $e->getMessage();
        echo "   {$name}: {$red}✗ Failed{$reset}\n";
    }
}

// 6. Check Queue Processing
echo "\n6. Checking Queue Processing...\n";
$horizonStatus = shell_exec('php artisan horizon:status 2>&1');
if (strpos($horizonStatus, 'Horizon is running') !== false) {
    echo "   Horizon: {$green}✓ Running{$reset}\n";
    $successes[] = "Horizon queue worker running";
} else {
    $warnings[] = "Horizon may not be running properly";
    echo "   Horizon: {$yellow}⚠ Check status{$reset}\n";
}

// Summary
echo "\n" . str_repeat('=', 50) . "\n";
echo "VALIDATION SUMMARY\n";
echo str_repeat('=', 50) . "\n\n";

if (count($successes) > 0) {
    echo "{$green}✓ SUCCESSES ({$reset}" . count($successes) . "{$green}):{$reset}\n";
    foreach ($successes as $success) {
        echo "   • {$success}\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "{$yellow}⚠ WARNINGS ({$reset}" . count($warnings) . "{$yellow}):{$reset}\n";
    foreach ($warnings as $warning) {
        echo "   • {$warning}\n";
    }
    echo "\n";
}

if (count($errors) > 0) {
    echo "{$red}✗ ERRORS ({$reset}" . count($errors) . "{$red}):{$reset}\n";
    foreach ($errors as $error) {
        echo "   • {$error}\n";
    }
    echo "\n";
}

// Overall status
if (count($errors) == 0) {
    if (count($warnings) == 0) {
        echo "\n{$green}✓ SYSTEM READY FOR PHONE TESTS!{$reset}\n";
        echo "All critical components are properly configured.\n";
    } else {
        echo "\n{$yellow}⚠ SYSTEM OPERATIONAL WITH WARNINGS{$reset}\n";
        echo "Phone tests can proceed, but review warnings above.\n";
    }
} else {
    echo "\n{$red}✗ SYSTEM NOT READY FOR PHONE TESTS!{$reset}\n";
    echo "Critical errors must be fixed before testing.\n";
}

echo "\n";

// Quick test command
if (count($errors) == 0) {
    echo "To simulate a test call, run:\n";
    echo "php artisan tinker --execute=\"app(App\Http\Controllers\Api\TestWebhookController::class)->simulateRetellWebhook(request());\"\n";
}

echo "\n";