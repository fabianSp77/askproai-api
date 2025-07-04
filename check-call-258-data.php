#!/usr/bin/env php
<?php

use App\Models\Call;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$call = Call::query()
    ->withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->find(258);

if ($call) {
    // Load relationships manually
    if ($call->company_id) {
        $call->company = \App\Models\Company::find($call->company_id);
    }
    if ($call->branch_id) {
        $call->branch = \App\Models\Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($call->branch_id);
    }
    $call->mlPrediction = \App\Models\MLCallPrediction::where('call_id', $call->id)->first();
}

if (!$call) {
    echo "Call 258 not found!\n";
    exit(1);
}

echo "=== Call 258 Vollständige Daten ===\n\n";

// Basis-Informationen
echo "1. BASIS-DATEN:\n";
echo "   - Status: " . $call->call_status . "\n";
echo "   - Dauer: " . $call->duration_sec . " Sekunden\n";
echo "   - Von: " . $call->from_number . "\n";
echo "   - An: " . $call->to_number . "\n";
echo "   - Agent: " . ($call->agent_name ?? 'N/A') . "\n";
echo "   - Disconnection: " . ($call->disconnection_reason ?? 'N/A') . "\n";

// Kosten & Einnahmen
echo "\n2. KOSTEN & EINNAHMEN:\n";
echo "   - Call Cost: " . ($call->cost ?? 0) . " EUR\n";
$callCost = is_array($call->webhook_data['call_cost'] ?? null) 
    ? json_encode($call->webhook_data['call_cost']) 
    : ($call->webhook_data['call_cost'] ?? 0);
echo "   - Retell Cost: " . $callCost . " USD\n";
echo "   - Company Rate: " . ($call->company?->call_rate ?? 0) . " EUR/Min\n";
echo "   - Berechnung: " . round($call->duration_sec / 60 * ($call->company?->call_rate ?? 0.10), 2) . " EUR\n";

// ML Prediction
echo "\n3. ML ANALYSE:\n";
if ($call->mlPrediction) {
    echo "   - Sentiment: " . $call->mlPrediction->sentiment_label . "\n";
    echo "   - Score: " . $call->mlPrediction->sentiment_score . "\n";
    echo "   - Intent: " . ($call->mlPrediction->intent ?? 'N/A') . "\n";
    echo "   - Confidence: " . round($call->mlPrediction->prediction_confidence * 100) . "%\n";
    echo "   - Top Features: " . json_encode($call->mlPrediction->top_features ?? []) . "\n";
} else {
    echo "   - KEINE ML PREDICTION VORHANDEN!\n";
}

// Analysis aus webhook_data
echo "\n4. CALL ANALYSIS (von Retell):\n";
$analysis = $call->webhook_data['call_analysis'] ?? [];
if ($analysis) {
    echo "   - User Sentiment: " . ($analysis['user_sentiment'] ?? 'N/A') . "\n";
    echo "   - Call Summary: " . ($analysis['call_summary'] ?? 'N/A') . "\n";
    echo "   - In Call Analysis: " . json_encode($analysis['in_call_analysis'] ?? []) . "\n";
    echo "   - Call Successful: " . ($analysis['call_successful'] ?? 'N/A') . "\n";
} else {
    echo "   - KEINE ANALYSIS DATEN!\n";
}

// Metadata
echo "\n5. METADATA:\n";
$metadata = $call->metadata ?? [];
echo "   - Customer Data: " . json_encode($metadata['customer_data'] ?? []) . "\n";
echo "   - Data Collected: " . ($metadata['customer_data_collected'] ?? 'false') . "\n";

// Extrahierte Daten
echo "\n6. EXTRAHIERTE DATEN:\n";
echo "   - Name: " . ($call->extracted_name ?? 'N/A') . "\n";
echo "   - Email: " . ($call->extracted_email ?? 'N/A') . "\n";
echo "   - Reason: " . ($call->reason_for_visit ?? 'N/A') . "\n";
echo "   - Summary: " . ($call->summary ?? 'N/A') . "\n";
echo "   - Appointment Requested: " . ($call->appointment_requested ? 'Ja' : 'Nein') . "\n";

// Weitere wichtige Felder
echo "\n7. WEITERE FELDER:\n";
echo "   - Latency: " . json_encode($call->webhook_data['latency'] ?? []) . "\n";
echo "   - First Visit: " . ($call->first_visit ? 'Ja' : 'Nein') . "\n";
echo "   - No Show Count: " . ($call->no_show_count ?? 0) . "\n";

echo "\n=== ENDE ===\n";