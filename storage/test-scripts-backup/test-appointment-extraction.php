#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Testing Appointment Extraction\n";
echo "================================\n\n";

$extractor = app(\App\Services\AppointmentExtractionService::class);

// Test cases
$testCases = [
    "Ich möchte gerne morgen um 16 Uhr einen Beratungstermin buchen.",
    "Ja, guten Tag, Hans Müller. Ich würde gern einen Termin für morgen 12 Uhr mittags haben.",
    "Ich hätte gerne einen Termin für heute um 15 Uhr",
    "Können Sie mir einen Termin für nächsten Montag um 10 Uhr machen?",
    "Ja, Hans Schuster mein Name. Ich möchte morgen 16 Uhr einen Termin vereinbaren."
];

foreach ($testCases as $i => $transcript) {
    echo "Test Case #" . ($i + 1) . ":\n";
    echo "Transcript: \"$transcript\"\n";
    
    $result = $extractor->extractFromTranscript($transcript);
    
    if ($result) {
        echo "✅ Extracted:\n";
        echo "   Date: " . $result['date'] . "\n";
        echo "   Time: " . $result['time'] . "\n";
        echo "   Service: " . ($result['service'] ?? 'N/A') . "\n";
        echo "   Customer: " . ($result['customer_name'] ?? 'N/A') . "\n";
        echo "   Confidence: " . $result['confidence'] . "%\n";
    } else {
        echo "❌ No appointment found\n";
    }
    
    echo "\n";
}

// Now check real calls
echo "\n🔍 Checking Real Calls:\n";
echo "━━━━━━━━━━━━━━━━━━━━━\n";

$calls = \DB::table('calls')
    ->where('transcript', 'LIKE', '%morgen%')
    ->whereNull('appointment_id')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($calls as $call) {
    echo "\nCall ID: {$call->id}\n";
    echo "From: {$call->from_number}\n";
    echo "Transcript excerpt: " . substr($call->transcript, 0, 200) . "...\n";
    
    $result = $extractor->extractFromTranscript($call->transcript);
    
    if ($result) {
        echo "✅ Can extract appointment:\n";
        echo "   Date: " . $result['date'] . "\n";
        echo "   Time: " . $result['time'] . "\n";
        
        // Show what would be created
        $startTime = \Carbon\Carbon::parse($result['date'] . ' ' . $result['time']);
        echo "   Would create appointment for: " . $startTime->format('d.m.Y H:i') . "\n";
    } else {
        echo "❌ Cannot extract appointment\n";
    }
}