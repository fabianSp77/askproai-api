<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\NameExtractor;
use App\Models\Call;

echo "🔍 EXTRACTING NAMES FROM TRANSCRIPTS\n";
echo "====================================\n\n";

$nameExtractor = new NameExtractor();

// First, let's check the latest call (ID 482)
$latestCall = Call::find(482);
if ($latestCall) {
    echo "Processing latest call (ID 482):\n";
    echo "  From: " . ($latestCall->from_number ?? 'null') . "\n";
    echo "  Current notes: " . ($latestCall->notes ?? 'null') . "\n";
    echo "  Has transcript: " . ($latestCall->transcript ? 'Yes' : 'No') . "\n";

    if ($nameExtractor->updateCallWithExtractedName($latestCall)) {
        $latestCall->refresh();
        echo "  ✅ Name extracted and saved: " . $latestCall->notes . "\n";
    } else {
        echo "  ❌ No name could be extracted\n";
    }
    echo "\n";
}

// Process all anonymous calls
echo "Processing all anonymous calls with transcripts...\n";
$updated = $nameExtractor->processAnonymousCalls();
echo "✅ Updated {$updated} anonymous calls with extracted names\n\n";

// Show results
echo "SAMPLE RESULTS:\n";
echo "===============\n";

$sampleCalls = Call::where('from_number', 'anonymous')
    ->whereNotNull('notes')
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

foreach ($sampleCalls as $call) {
    echo "Call ID {$call->id}:\n";
    echo "  Notes: {$call->notes}\n";
    echo "  Created: {$call->created_at}\n\n";
}

// Also check calls that still don't have names
$callsWithoutNames = Call::where('from_number', 'anonymous')
    ->where(function($q) {
        $q->whereNull('notes')
          ->orWhere('notes', '');
    })
    ->whereNotNull('transcript')
    ->count();

echo "Remaining anonymous calls without names: {$callsWithoutNames}\n";

if ($callsWithoutNames > 0) {
    echo "\nChecking why names couldn't be extracted:\n";
    $problemCalls = Call::where('from_number', 'anonymous')
        ->where(function($q) {
            $q->whereNull('notes')
              ->orWhere('notes', '');
        })
        ->whereNotNull('transcript')
        ->take(2)
        ->get();

    foreach ($problemCalls as $call) {
        echo "\nCall ID {$call->id}:\n";
        echo "  Transcript excerpt: " . substr($call->transcript, 0, 200) . "...\n";
    }
}

echo "\n✨ Processing complete!\n";