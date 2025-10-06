#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test the exact extraction logic
$data = [
    'event' => 'call_inbound',
    'call_id' => null,
    'from_number' => '+491777888999',
    'to_number' => '+493083793369',  // AskProAI phone number
    'timestamp' => now()->toIso8601String()
];

$event = $data['event'] ?? $data['event_type'] ?? null;

// Check the extraction logic
if ($event === 'call_inbound' && isset($data['call_inbound'])) {
    $callData = $data['call_inbound'];
    echo "Using call_inbound field\n";
} else {
    $callData = $data['call'] ?? $data;
    echo "Using full data or call field\n";
}

echo "CallData: " . json_encode($callData) . "\n";

$callId = $callData['call_id'] ?? $callData['id'] ?? null;
$fromNumber = $callData['from_number'] ?? $callData['from'] ?? $callData['caller'] ?? null;
$toNumber = $callData['to_number'] ?? $callData['to'] ?? $callData['callee'] ?? null;

echo "Extracted - callId: " . ($callId ?: 'null') . ", from: $fromNumber, to: $toNumber\n";
echo "Will create temp call: " . ((!$callId && ($fromNumber || $toNumber)) ? 'YES' : 'NO') . "\n";

// Now test actual webhook
$controller = new \App\Http\Controllers\RetellWebhookController();
$request = new \Illuminate\Http\Request([], [], [], [], [], [], json_encode($data));
$request->headers->set('Content-Type', 'application/json');
$request->setMethod('POST');
$response = $controller->__invoke($request);
echo "Response: " . $response->getContent() . "\n";

// Check if call was created
$call = \App\Models\Call::where('from_number', '+491777888999')
    ->orderBy('created_at', 'desc')
    ->first();
if ($call) {
    echo "Call created: ID=" . $call->id . ", retell_call_id=" . $call->retell_call_id . ", company_id=" . $call->company_id . "\n";
} else {
    echo "No call created\n";
}