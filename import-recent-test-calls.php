#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Models\Call;
use App\Services\RetellV2Service;
use App\Services\PhoneNumberResolver;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Set trusted company context for company 1
\App\Traits\BelongsToCompany::setTrustedCompanyContext(1, 'import-script');

try {
    $company = Company::find(1);
    if (!$company || !$company->retell_api_key) {
        echo "Company 1 needs Retell API key configured.\n";
        exit(1);
    }

    echo "Fetching recent calls from Retell API...\n";
    
    $retellService = new RetellV2Service($company->retell_api_key);
    
    // Get recent calls
    $response = $retellService->listCalls(50);
    
    $calls = $response['calls'] ?? [];
    echo "Found " . count($calls) . " calls in the last 24 hours.\n";
    
    $phoneResolver = app(PhoneNumberResolver::class);
    $imported = 0;
    $skipped = 0;
    
    foreach ($calls as $callData) {
        // Check if call already exists
        $existingCall = Call::where('retell_call_id', $callData['call_id'])->first();
        if ($existingCall) {
            $skipped++;
            continue;
        }
        
        // Resolve company and branch from phone number
        $context = $phoneResolver->resolveFromWebhook([
            'to' => $callData['to_number'] ?? null,
            'from' => $callData['from_number'] ?? null,
            'agent_id' => $callData['agent_id'] ?? null,
        ]);
        
        $companyId = $context['company_id'] ?? 1;
        $branchId = $context['branch_id'] ?? null;
        
        // Create call record
        $call = Call::create([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'retell_call_id' => $callData['call_id'],
            'from_number' => $callData['from_number'] ?? 'unknown',
            'to_number' => $callData['to_number'] ?? 'unknown',
            'direction' => $callData['direction'] ?? 'inbound',
            'call_type' => $callData['call_type'] ?? 'phone_call',
            'call_status' => $callData['call_status'] ?? 'completed',
            'agent_id' => $callData['agent_id'] ?? null,
            'start_timestamp' => isset($callData['start_timestamp']) 
                ? Carbon::createFromTimestampMs($callData['start_timestamp'])->addHours(2) 
                : null,
            'end_timestamp' => isset($callData['end_timestamp']) 
                ? Carbon::createFromTimestampMs($callData['end_timestamp'])->addHours(2) 
                : null,
            'duration_sec' => $callData['call_length'] ?? 0,
            'transcript' => $callData['transcript'] ?? '',
            'transcript_object' => $callData['transcript_object'] ?? [],
            'recording_url' => $callData['recording_url'] ?? null,
            'public_log_url' => $callData['public_log_url'] ?? null,
            'analysis' => $callData['call_analysis'] ?? [],
            'metadata' => $callData['metadata'] ?? [],
            'disconnection_reason' => $callData['disconnection_reason'] ?? null,
        ]);
        
        echo "Imported call {$call->retell_call_id}";
        echo " | From: {$call->from_number} | To: {$call->to_number}";
        echo " | Company: {$call->company_id} | Branch: " . ($call->branch_id ?: 'NULL');
        echo " | Duration: {$call->duration_sec}s\n";
        
        $imported++;
    }
    
    echo "\nImport complete! Imported: $imported, Skipped: $skipped\n";
    
    // Show recent calls from user's number
    echo "\nRecent calls from +491604366218:\n";
    $userCalls = Call::where('from_number', 'LIKE', '%1604366218%')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
        
    foreach ($userCalls as $call) {
        echo "- [{$call->created_at}] {$call->retell_call_id} | Status: {$call->call_status} | Branch: " . ($call->branch_id ?: 'NULL') . "\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
} finally {
    // Clear company context
    \App\Traits\BelongsToCompany::clearCompanyContext();
}