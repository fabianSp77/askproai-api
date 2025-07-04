<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WebhookEvent;
use App\Models\Call;
use App\Models\Appointment;
use App\Scopes\TenantScope;
use Carbon\Carbon;

echo "=== Checking Latest Call and Appointment ===\n\n";

// Get the latest webhook events from today
echo "1. Latest Webhook Events (last 30 minutes):\n";
echo str_repeat("-", 100) . "\n";

$recentWebhooks = WebhookEvent::withoutGlobalScope(TenantScope::class)
    ->where('provider', 'retell')
    ->where('created_at', '>=', Carbon::now()->subHours(2))
    ->orderBy('created_at', 'desc')
    ->get();

foreach ($recentWebhooks as $webhook) {
    echo "ID: {$webhook->id} | Type: {$webhook->event_type} | Status: {$webhook->status}\n";
    echo "Created: {$webhook->created_at}\n";
    
    $payload = is_string($webhook->payload) ? json_decode($webhook->payload, true) : $webhook->payload;
    
    if (isset($payload['call']['call_id'])) {
        echo "Call ID: {$payload['call']['call_id']}\n";
    }
    
    // Check for appointment data
    $hasAppointmentData = false;
    
    // Check in call_analysis
    if (isset($payload['call']['call_analysis']['custom_analysis_data'])) {
        $customData = $payload['call']['call_analysis']['custom_analysis_data'];
        if (isset($customData['appointment_made'])) {
            echo "Appointment Made: " . ($customData['appointment_made'] ? 'YES' : 'NO') . "\n";
            $hasAppointmentData = true;
        }
        if (isset($customData['appointment_date_time'])) {
            echo "Appointment Time: {$customData['appointment_date_time']}\n";
        }
        if (isset($customData['caller_full_name'])) {
            echo "Caller Name: {$customData['caller_full_name']}\n";
        }
    }
    
    // Check in retell_llm_dynamic_variables
    if (isset($payload['call']['retell_llm_dynamic_variables'])) {
        $dynamicVars = $payload['call']['retell_llm_dynamic_variables'];
        echo "Dynamic Variables Found:\n";
        foreach ($dynamicVars as $key => $value) {
            if (!is_array($value)) {
                echo "  - $key: $value\n";
            }
        }
        
        // Check for appointment data
        if (isset($dynamicVars['appointment_data'])) {
            echo "  ✅ Has appointment_data\n";
            $hasAppointmentData = true;
            print_r($dynamicVars['appointment_data']);
        }
    }
    
    // Check cache for appointment data
    if (isset($payload['call']['call_id'])) {
        $cacheKey = "retell:appointment:{$payload['call']['call_id']}";
        $cachedData = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cachedData) {
            echo "✅ Found cached appointment data:\n";
            print_r($cachedData);
            $hasAppointmentData = true;
        }
    }
    
    if (!$hasAppointmentData) {
        echo "❌ No appointment data found in webhook\n";
    }
    
    echo str_repeat("-", 100) . "\n";
}

// Check latest calls
echo "\n2. Latest Calls (last 30 minutes):\n";
echo str_repeat("-", 100) . "\n";

$recentCalls = Call::withoutGlobalScope(TenantScope::class)
    ->where('created_at', '>=', Carbon::now()->subHours(2))
    ->orderBy('created_at', 'desc')
    ->get();

foreach ($recentCalls as $call) {
    echo "Call ID: {$call->id} | Retell ID: {$call->retell_call_id}\n";
    echo "From: {$call->from_number} | To: {$call->to_number}\n";
    echo "Status: {$call->status} | Created: {$call->created_at}\n";
    echo "Has Appointment: " . ($call->appointment_id ? "YES (ID: {$call->appointment_id})" : "NO") . "\n";
    
    if ($call->analysis_data) {
        $analysisData = is_string($call->analysis_data) ? json_decode($call->analysis_data, true) : $call->analysis_data;
        if ($analysisData) {
            echo "Analysis Data:\n";
            foreach ($analysisData as $key => $value) {
                if (!is_array($value)) {
                    echo "  - $key: $value\n";
                }
            }
        }
    }
    
    echo str_repeat("-", 50) . "\n";
}

// Check today's appointments
echo "\n3. Today's Appointments:\n";
echo str_repeat("-", 100) . "\n";

$todayAppointments = Appointment::withoutGlobalScope(TenantScope::class)
    ->whereDate('created_at', Carbon::today())
    ->orderBy('created_at', 'desc')
    ->get();

if ($todayAppointments->count() > 0) {
    foreach ($todayAppointments as $appointment) {
        echo "Appointment ID: {$appointment->id}\n";
        echo "Created: {$appointment->created_at}\n";
        echo "Start Time: {$appointment->start_time}\n";
        echo "Status: {$appointment->status}\n";
        echo "Customer ID: {$appointment->customer_id}\n";
        
        // Check if linked to a call
        $linkedCall = Call::withoutGlobalScope(TenantScope::class)
            ->where('appointment_id', $appointment->id)
            ->first();
            
        if ($linkedCall) {
            echo "✅ Created from Call: {$linkedCall->retell_call_id}\n";
        } else {
            echo "📝 Not linked to a call\n";
        }
        
        echo str_repeat("-", 50) . "\n";
    }
} else {
    echo "No appointments created today\n";
}

// Check for appointments scheduled for today 16:00
echo "\n4. Appointments for Today 16:00:\n";
echo str_repeat("-", 100) . "\n";

$today16 = Carbon::today()->setHour(16)->setMinute(0);
$appointmentsAt16 = Appointment::withoutGlobalScope(TenantScope::class)
    ->where('start_time', '>=', $today16->copy()->subMinutes(30))
    ->where('start_time', '<=', $today16->copy()->addMinutes(30))
    ->get();

if ($appointmentsAt16->count() > 0) {
    foreach ($appointmentsAt16 as $appointment) {
        echo "Found Appointment ID: {$appointment->id}\n";
        echo "Start Time: {$appointment->start_time}\n";
        echo "Status: {$appointment->status}\n";
        echo "Created: {$appointment->created_at}\n";
    }
} else {
    echo "❌ No appointments found for 16:00 today\n";
}

// Check failed jobs
echo "\n5. Recent Failed Jobs:\n";
echo str_repeat("-", 100) . "\n";

$failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')
    ->where('failed_at', '>=', Carbon::now()->subMinutes(30))
    ->orderBy('failed_at', 'desc')
    ->limit(5)
    ->get();

if ($failedJobs->count() > 0) {
    foreach ($failedJobs as $job) {
        echo "Job: {$job->queue} | Failed at: {$job->failed_at}\n";
        
        $payload = json_decode($job->payload, true);
        if (isset($payload['displayName'])) {
            echo "Type: {$payload['displayName']}\n";
        }
        
        $exception = json_decode($job->exception, true);
        if ($exception) {
            echo "Error: " . substr($exception['message'] ?? 'Unknown error', 0, 200) . "\n";
        }
        
        echo str_repeat("-", 50) . "\n";
    }
} else {
    echo "No recent failed jobs\n";
}

echo "\n=== Check Complete ===\n";