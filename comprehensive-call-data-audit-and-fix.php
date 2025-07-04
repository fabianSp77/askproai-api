<?php

/**
 * Comprehensive Call Data Audit and Fix
 * 
 * Dieses Script:
 * 1. Prüft ALLE Calls auf fehlende/falsche Daten
 * 2. Holt fehlende Daten von Retell API
 * 3. Korrigiert Datenformate und Mappings
 * 4. Erstellt Bericht über Datenqualität
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use App\Services\RetellV2Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n=== Comprehensive Call Data Audit and Fix ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Get company and Retell service
$company = Company::first();
if (!$company || !$company->retell_api_key) {
    echo "❌ No company with Retell API key found\n";
    exit(1);
}

try {
    $apiKey = is_string($company->retell_api_key) && strpos($company->retell_api_key, 'key_') === 0 
        ? $company->retell_api_key 
        : decrypt($company->retell_api_key);
        
    $retellService = new RetellV2Service($apiKey);
} catch (\Exception $e) {
    echo "❌ Could not decrypt API key: " . $e->getMessage() . "\n";
    exit(1);
}

// Statistics
$stats = [
    'total_calls' => 0,
    'calls_updated' => 0,
    'fields_fixed' => [],
    'errors' => 0,
    'api_calls_made' => 0
];

// Define all expected fields from Retell
$retellFields = [
    // Basic fields
    'call_id' => 'retell_call_id',
    'agent_id' => 'agent_id',
    'agent_version' => 'agent_version',
    'call_type' => 'call_type',
    'from_number' => 'from_number',
    'to_number' => 'to_number',
    'direction' => 'direction',
    'call_status' => 'call_status',
    'session_outcome' => 'session_outcome',
    'disconnection_reason' => 'disconnection_reason',
    
    // Timestamps and duration
    'start_timestamp' => 'start_timestamp',
    'end_timestamp' => 'end_timestamp',
    'call_analysis.call_length' => 'duration_sec',
    
    // Content
    'transcript' => 'transcript',
    'transcript_object' => 'transcript_object',
    'transcript_with_tool_calls' => 'transcript_with_tools',
    'recording_url' => 'recording_url',
    'public_log_url' => 'public_log_url',
    
    // Analysis
    'call_analysis.call_summary' => 'summary',
    'call_analysis.user_sentiment' => 'sentiment',
    'cost' => 'cost',
    'end_to_end_latency' => 'end_to_end_latency',
    'latency' => 'latency_metrics',
    
    // Dynamic variables (handled separately)
    'retell_llm_dynamic_variables' => 'retell_dynamic_variables',
    'metadata' => 'metadata'
];

// Get all calls
echo "1. Fetching all calls from database...\n";
$calls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->orderBy('created_at', 'desc')
    ->get();

$stats['total_calls'] = $calls->count();
echo "Found {$stats['total_calls']} calls in database\n\n";

// Process in batches to avoid API rate limits
$batchSize = 50;
$batches = $calls->chunk($batchSize);

foreach ($batches as $batchIndex => $batch) {
    echo "2. Processing batch " . ($batchIndex + 1) . " of " . ceil($stats['total_calls'] / $batchSize) . "...\n";
    
    // Get call IDs for API request
    $callIds = $batch->pluck('retell_call_id')->filter()->values()->toArray();
    
    if (empty($callIds)) {
        echo "   No valid call IDs in this batch\n";
        continue;
    }
    
    // Fetch from Retell API
    try {
        echo "   Fetching " . count($callIds) . " calls from Retell API...\n";
        $response = $retellService->listCalls(50); // Get recent calls
        $stats['api_calls_made']++;
        
        if (!isset($response['calls'])) {
            echo "   ⚠️  No calls returned from API\n";
            continue;
        }
        
        $apiCalls = collect($response['calls'])->keyBy('call_id');
        
    } catch (\Exception $e) {
        echo "   ❌ API Error: " . $e->getMessage() . "\n";
        $stats['errors']++;
        continue;
    }
    
    // Process each call in batch
    foreach ($batch as $call) {
        $callId = $call->retell_call_id ?: $call->call_id;
        
        if (!$callId) {
            echo "   ⚠️  Call {$call->id} has no call_id\n";
            continue;
        }
        
        // Get API data if available
        $apiData = $apiCalls->get($callId);
        
        if (!$apiData) {
            // Try to fetch individual call
            try {
                // Note: Retell API might not have individual call endpoint
                // Skip if not in batch
                continue;
            } catch (\Exception $e) {
                continue;
            }
        }
        
        $updates = [];
        $updatedFields = [];
        
        // Check and fix each field
        foreach ($retellFields as $apiField => $dbField) {
            $apiValue = data_get($apiData, $apiField);
            $dbValue = $call->$dbField;
            
            // Skip if API doesn't have this field
            if ($apiValue === null) {
                continue;
            }
            
            // Special handling for different field types
            switch ($dbField) {
                case 'start_timestamp':
                case 'end_timestamp':
                    if ($apiValue) {
                        $newValue = \Carbon\Carbon::createFromTimestampMs($apiValue);
                        if (!$dbValue || $newValue->format('Y-m-d H:i:s') !== $dbValue) {
                            $updates[$dbField] = $newValue;
                            $updatedFields[] = $dbField;
                        }
                    }
                    break;
                    
                case 'duration_sec':
                    // Prefer call_length from analysis
                    $correctDuration = $apiValue;
                    if (!$correctDuration && $call->start_timestamp && $call->end_timestamp) {
                        $correctDuration = abs($call->end_timestamp->diffInSeconds($call->start_timestamp));
                    }
                    if ($correctDuration && $dbValue != $correctDuration) {
                        $updates[$dbField] = $correctDuration;
                        $updatedFields[] = $dbField;
                    }
                    break;
                    
                case 'retell_dynamic_variables':
                    if ($apiValue && (empty($dbValue) || $dbValue === '[]')) {
                        $updates[$dbField] = $apiValue;
                        $updatedFields[] = $dbField;
                        
                        // Parse dynamic variables
                        $parsed = parseDynamicVariables($apiValue);
                        
                        // Update related fields
                        if (!empty($parsed['name']) && empty($call->name)) {
                            $updates['name'] = $parsed['name'];
                            $updatedFields[] = 'name';
                        }
                        
                        if (!empty($parsed['email']) && empty($call->email)) {
                            $updates['email'] = $parsed['email'];
                            $updatedFields[] = 'email';
                        }
                        
                        if (!empty($parsed['phone_number']) && empty($call->phone_number)) {
                            $updates['phone_number'] = $parsed['phone_number'];
                            $updatedFields[] = 'phone_number';
                        }
                        
                        if (!empty($parsed['datum_termin']) && empty($call->datum_termin)) {
                            $updates['datum_termin'] = $parsed['datum_termin'];
                            $updatedFields[] = 'datum_termin';
                        }
                        
                        if (!empty($parsed['uhrzeit_termin']) && empty($call->uhrzeit_termin)) {
                            $updates['uhrzeit_termin'] = $parsed['uhrzeit_termin'];
                            $updatedFields[] = 'uhrzeit_termin';
                        }
                        
                        if (!empty($parsed['dienstleistung']) && empty($call->dienstleistung)) {
                            $updates['dienstleistung'] = $parsed['dienstleistung'];
                            $updatedFields[] = 'dienstleistung';
                        }
                        
                        if (!empty($parsed['health_insurance_company']) && empty($call->health_insurance_company)) {
                            $updates['health_insurance_company'] = $parsed['health_insurance_company'];
                            $updatedFields[] = 'health_insurance_company';
                        }
                        
                        // Set appointment_made flag
                        $appointmentMade = checkAppointmentMade($parsed);
                        if ($appointmentMade && !$call->appointment_made) {
                            $updates['appointment_made'] = true;
                            $updatedFields[] = 'appointment_made';
                        }
                    }
                    break;
                    
                default:
                    // Simple field update
                    if ($apiValue && empty($dbValue)) {
                        $updates[$dbField] = $apiValue;
                        $updatedFields[] = $dbField;
                    }
                    break;
            }
        }
        
        // Ensure both call_id fields are set
        if (empty($call->call_id) && !empty($call->retell_call_id)) {
            $updates['call_id'] = $call->retell_call_id;
            $updatedFields[] = 'call_id';
        }
        if (empty($call->retell_call_id) && !empty($call->call_id)) {
            $updates['retell_call_id'] = $call->call_id;
            $updatedFields[] = 'retell_call_id';
        }
        
        // Ensure both agent_id fields are set
        if (empty($call->agent_id) && !empty($call->retell_agent_id)) {
            $updates['agent_id'] = $call->retell_agent_id;
            $updatedFields[] = 'agent_id';
        }
        if (empty($call->retell_agent_id) && !empty($call->agent_id)) {
            $updates['retell_agent_id'] = $call->agent_id;
            $updatedFields[] = 'retell_agent_id';
        }
        
        // Apply updates if any
        if (!empty($updates)) {
            try {
                foreach ($updates as $field => $value) {
                    $call->$field = $value;
                }
                $call->save();
                
                $stats['calls_updated']++;
                foreach ($updatedFields as $field) {
                    $stats['fields_fixed'][$field] = ($stats['fields_fixed'][$field] ?? 0) + 1;
                }
                
                echo "   ✅ Updated call {$callId} with " . count($updatedFields) . " fields\n";
                
            } catch (\Exception $e) {
                echo "   ❌ Error updating call {$callId}: " . $e->getMessage() . "\n";
                $stats['errors']++;
            }
        }
    }
    
    // Sleep to avoid rate limiting
    if ($batchIndex < count($batches) - 1) {
        echo "   Waiting 2 seconds before next batch...\n";
        sleep(2);
    }
}

// Generate report
echo "\n3. Generating data quality report...\n";

$report = DB::select("
    SELECT 
        COUNT(*) as total_calls,
        SUM(CASE WHEN call_id IS NOT NULL THEN 1 ELSE 0 END) as with_call_id,
        SUM(CASE WHEN retell_call_id IS NOT NULL THEN 1 ELSE 0 END) as with_retell_call_id,
        SUM(CASE WHEN agent_id IS NOT NULL THEN 1 ELSE 0 END) as with_agent_id,
        SUM(CASE WHEN agent_version IS NOT NULL THEN 1 ELSE 0 END) as with_agent_version,
        SUM(CASE WHEN duration_sec IS NOT NULL AND duration_sec > 0 THEN 1 ELSE 0 END) as with_duration,
        SUM(CASE WHEN transcript IS NOT NULL THEN 1 ELSE 0 END) as with_transcript,
        SUM(CASE WHEN summary IS NOT NULL THEN 1 ELSE 0 END) as with_summary,
        SUM(CASE WHEN sentiment IS NOT NULL THEN 1 ELSE 0 END) as with_sentiment,
        SUM(CASE WHEN cost IS NOT NULL THEN 1 ELSE 0 END) as with_cost,
        SUM(CASE WHEN public_log_url IS NOT NULL THEN 1 ELSE 0 END) as with_public_url,
        SUM(CASE WHEN retell_dynamic_variables IS NOT NULL THEN 1 ELSE 0 END) as with_dynamic_vars,
        SUM(CASE WHEN session_outcome IS NOT NULL THEN 1 ELSE 0 END) as with_outcome,
        SUM(CASE WHEN appointment_made = 1 THEN 1 ELSE 0 END) as appointments_made,
        SUM(CASE WHEN name IS NOT NULL THEN 1 ELSE 0 END) as with_customer_name,
        SUM(CASE WHEN email IS NOT NULL THEN 1 ELSE 0 END) as with_email,
        SUM(CASE WHEN datum_termin IS NOT NULL THEN 1 ELSE 0 END) as with_appointment_date,
        SUM(CASE WHEN uhrzeit_termin IS NOT NULL THEN 1 ELSE 0 END) as with_appointment_time,
        SUM(CASE WHEN dienstleistung IS NOT NULL THEN 1 ELSE 0 END) as with_service
    FROM calls
")[0];

echo "\n=== Data Quality Report ===\n";
echo "Total Calls: {$report->total_calls}\n";
echo "\nField Completeness:\n";
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Call ID", $report->with_call_id, $report->total_calls, ($report->with_call_id / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Retell Call ID", $report->with_retell_call_id, $report->total_calls, ($report->with_retell_call_id / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Agent ID", $report->with_agent_id, $report->total_calls, ($report->with_agent_id / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Agent Version", $report->with_agent_version, $report->total_calls, ($report->with_agent_version / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Duration", $report->with_duration, $report->total_calls, ($report->with_duration / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Transcript", $report->with_transcript, $report->total_calls, ($report->with_transcript / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Summary", $report->with_summary, $report->total_calls, ($report->with_summary / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Sentiment", $report->with_sentiment, $report->total_calls, ($report->with_sentiment / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Cost", $report->with_cost, $report->total_calls, ($report->with_cost / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Public URL", $report->with_public_url, $report->total_calls, ($report->with_public_url / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Dynamic Variables", $report->with_dynamic_vars, $report->total_calls, ($report->with_dynamic_vars / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Session Outcome", $report->with_outcome, $report->total_calls, ($report->with_outcome / $report->total_calls) * 100);

echo "\nAppointment Data:\n";
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Appointments Made", $report->appointments_made, $report->total_calls, ($report->appointments_made / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Customer Name", $report->with_customer_name, $report->total_calls, ($report->with_customer_name / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Email", $report->with_email, $report->total_calls, ($report->with_email / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Appointment Date", $report->with_appointment_date, $report->total_calls, ($report->with_appointment_date / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Appointment Time", $report->with_appointment_time, $report->total_calls, ($report->with_appointment_time / $report->total_calls) * 100);
echo sprintf("%-30s: %d/%d (%.1f%%)\n", "Service", $report->with_service, $report->total_calls, ($report->with_service / $report->total_calls) * 100);

echo "\n=== Update Summary ===\n";
echo "Total calls processed: {$stats['total_calls']}\n";
echo "Calls updated: {$stats['calls_updated']}\n";
echo "API calls made: {$stats['api_calls_made']}\n";
echo "Errors: {$stats['errors']}\n";

if (!empty($stats['fields_fixed'])) {
    echo "\nFields fixed:\n";
    arsort($stats['fields_fixed']);
    foreach ($stats['fields_fixed'] as $field => $count) {
        echo "  - $field: $count times\n";
    }
}

echo "\n✅ Audit and fix complete!\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";

// Helper functions
function parseDynamicVariables($dynamicVars): array
{
    $normalized = [];
    
    if (!is_array($dynamicVars)) {
        return $normalized;
    }
    
    foreach ($dynamicVars as $key => $value) {
        // Skip template variables
        if (is_string($value) && str_contains($value, '{{')) {
            continue;
        }
        
        // Remove leading underscores
        $cleanKey = ltrim($key, '_');
        
        // Replace double underscores with single
        $cleanKey = str_replace('__', '_', $cleanKey);
        
        // Map to our field names
        $fieldMap = [
            'datum_termin' => 'datum_termin',
            'uhrzeit_termin' => 'uhrzeit_termin',
            'appointment_date_time' => 'appointment_datetime',
            'patient_full_name' => 'name',
            'caller_full_name' => 'name',
            'name' => 'name',
            'telefonnummer_anrufer' => 'phone_number',
            'caller_phone' => 'phone_number',
            'reason_for_visit' => 'dienstleistung',
            'dienstleistung' => 'dienstleistung',
            'zusammenfassung_anruf' => 'summary',
            'information_anruf' => 'notes',
            'email' => 'email',
            'health_insurance_company' => 'health_insurance_company',
            'insurance_type' => 'health_insurance_company'
        ];
        
        if (isset($fieldMap[$cleanKey])) {
            $normalized[$fieldMap[$cleanKey]] = $value;
        } else {
            $normalized[$cleanKey] = $value;
        }
    }
    
    return $normalized;
}

function checkAppointmentMade($parsedVars): bool
{
    // Check for explicit appointment_made flag
    if (isset($parsedVars['appointment_made'])) {
        return filter_var($parsedVars['appointment_made'], FILTER_VALIDATE_BOOLEAN);
    }
    
    // Check if we have minimum required appointment data
    $hasDate = !empty($parsedVars['datum_termin']) || !empty($parsedVars['appointment_datetime']);
    $hasTime = !empty($parsedVars['uhrzeit_termin']) || !empty($parsedVars['appointment_datetime']);
    $hasService = !empty($parsedVars['dienstleistung']);
    $hasName = !empty($parsedVars['name']);
    
    return $hasDate && $hasTime && $hasService && $hasName;
}