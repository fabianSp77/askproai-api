<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Services\NameExtractor;

echo "\n🔍 CHECKING NAME EXTRACTION PROCESS\n";
echo "====================================\n\n";

// Get the latest call
$latestCall = Call::where('company_id', 15)
    ->orderBy('created_at', 'desc')
    ->first();

echo "📞 LATEST CALL DETAILS:\n";
echo "------------------------\n";
echo "Call ID: {$latestCall->id}\n";
echo "Time: {$latestCall->created_at}\n";
echo "From: {$latestCall->from_number}\n";
echo "Customer Name Field: " . ($latestCall->customer_name ?: 'NULL') . "\n";
echo "Customer ID: " . ($latestCall->customer_id ?: 'NULL') . "\n";
echo "Notes: " . ($latestCall->notes ?: 'NULL') . "\n\n";

echo "📝 TRANSCRIPT ANALYSIS:\n";
echo "------------------------\n";

if ($latestCall->transcript) {
    $transcript = is_string($latestCall->transcript) ? $latestCall->transcript : json_encode($latestCall->transcript);

    // Show first 500 chars of transcript
    echo "Transcript Preview (first 500 chars):\n";
    echo substr($transcript, 0, 500) . "...\n\n";

    // Search for name patterns
    echo "SEARCHING FOR NAME PATTERNS:\n";

    $patterns = [
        '/mein Name ist ([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)/i' => 'mein Name ist [NAME]',
        '/ich (?:heiße|bin) ([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)/i' => 'ich heiße/bin [NAME]',
        '/([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?) ist mein Name/i' => '[NAME] ist mein Name',
        '/Kunde: Ich (?:heiße|bin) ([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)/i' => 'Kunde: Ich heiße/bin [NAME]',
        '/Kunde: ([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)[,\.]?\s*(?:Ich|Guten|Hallo)/i' => 'Kunde: [NAME] ...',
        '/Kunde:\s*([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)\s*$/im' => 'Kunde: [NAME]',
        '/Customer:\s*([A-Z][a-z]+(?:\s+[A-Z][a-z]+)?)/i' => 'Customer: [NAME]',
    ];

    $foundName = false;
    foreach ($patterns as $pattern => $description) {
        if (preg_match($pattern, $transcript, $matches)) {
            echo "✅ FOUND with pattern: {$description}\n";
            echo "   Extracted name: {$matches[1]}\n";
            $foundName = true;
        }
    }

    if (!$foundName) {
        echo "❌ No name found with standard patterns\n\n";

        // Look for any occurrence of "Kunde:" lines
        echo "SEARCHING FOR CUSTOMER DIALOGUE:\n";
        if (preg_match_all('/Kunde: ([^\n]+)/i', $transcript, $matches)) {
            echo "Found " . count($matches[1]) . " customer lines:\n";
            foreach ($matches[1] as $i => $line) {
                if ($i < 5) { // Show first 5 lines
                    echo "   - " . substr($line, 0, 100) . "\n";
                }
            }
        }
    }
} else {
    echo "❌ No transcript available for this call\n";
}

echo "\n\n🔧 TESTING NAME EXTRACTOR SERVICE:\n";
echo "------------------------------------\n";

$nameExtractor = new NameExtractor();
$extractedName = $nameExtractor->extractNameFromCall($latestCall);

if ($extractedName) {
    echo "✅ NameExtractor found: {$extractedName}\n";

    // Try to update the call
    echo "\nUpdating call with extracted name...\n";
    $result = $nameExtractor->updateCallWithExtractedName($latestCall);
    if ($result) {
        echo "✅ Call updated successfully\n";
    } else {
        echo "❌ Call update failed (might already have a name)\n";
    }
} else {
    echo "❌ NameExtractor could not find a name\n";
}

echo "\n\n📋 RETELL WEBHOOK PROCESS CHECK:\n";
echo "----------------------------------\n";

// Check if RetellWebhookController uses NameExtractor
$webhookControllerPath = __DIR__ . '/app/Http/Controllers/RetellWebhookController.php';
$webhookContent = file_get_contents($webhookControllerPath);

if (strpos($webhookContent, 'NameExtractor') !== false) {
    echo "✅ RetellWebhookController uses NameExtractor\n";

    // Check where it's called
    if (strpos($webhookContent, 'updateCallWithExtractedName') !== false) {
        echo "✅ updateCallWithExtractedName is called\n";
    } else {
        echo "❌ updateCallWithExtractedName is NOT called\n";
    }

    // Check conditions
    if (preg_match('/if.*anonymous.*NameExtractor/s', $webhookContent)) {
        echo "⚠️ NameExtractor only runs for anonymous calls\n";
    }
} else {
    echo "❌ RetellWebhookController does NOT use NameExtractor\n";
}

echo "\n\n💡 PROCESS FLOW:\n";
echo "----------------\n";
echo "1. Retell sends webhook events (call_inbound, call_ended, call_analyzed)\n";
echo "2. RetellWebhookController receives the event\n";
echo "3. For 'call_analyzed' event with transcript:\n";
echo "   - Call record is updated with transcript\n";
echo "   - If from_number is 'anonymous', NameExtractor runs\n";
echo "   - NameExtractor tries to extract name from transcript\n";
echo "   - If name found, customer_name field is updated\n";
echo "4. CallResource displays the name in the table\n";

echo "\n\n⚠️ POTENTIAL ISSUES:\n";
echo "---------------------\n";

if ($latestCall->transcript) {
    // Check if it's a dialog format issue
    if (strpos($transcript, 'Kunde:') === false && strpos($transcript, 'Customer:') === false) {
        echo "• Transcript might not be in dialog format (no 'Kunde:' prefix found)\n";
    }

    // Check if call_analyzed was received
    if (!$latestCall->customer_name && $latestCall->from_number === 'anonymous') {
        echo "• Name extraction might have failed or webhook didn't run\n";
        echo "• Check if call_analyzed webhook was received for this call\n";
    }
}

echo "\n✨ Analysis complete!\n";