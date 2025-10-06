<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Services\NameExtractor;

echo "\n🔧 UPDATING LATEST CALL WITH EXTRACTED NAME\n";
echo "============================================\n\n";

// Get the latest call
$latestCall = Call::where('company_id', 15)
    ->orderBy('created_at', 'desc')
    ->first();

echo "📞 Latest Call (ID: {$latestCall->id})\n";
echo "   Time: {$latestCall->created_at}\n";
echo "   Current customer_name: " . ($latestCall->customer_name ?: 'NULL') . "\n\n";

// Use NameExtractor
$nameExtractor = new NameExtractor();

echo "🔍 Extracting name from transcript...\n";
$extractedName = $nameExtractor->extractNameFromCall($latestCall);

if ($extractedName) {
    echo "   ✅ Found name: {$extractedName}\n\n";

    echo "📝 Updating call record...\n";
    $latestCall->customer_name = $extractedName;
    $latestCall->save();
    echo "   ✅ Call updated with customer_name: {$extractedName}\n";
} else {
    echo "   ❌ No name found - trying manual extraction...\n\n";

    // Manual extraction for "User: guten Tag, Hans Schuster" format
    if ($latestCall->transcript) {
        $transcript = $latestCall->transcript;

        // Pattern specifically for "User: Ja, guten Tag, Hans Schuster"
        $pattern = '/User:\s*(?:Ja,?\s*)?(?:guten Tag|Guten Tag),?\s*([A-ZÄÖÜ][a-zäöüß]+\s+[A-ZÄÖÜ][a-zäöüß]+)/i';

        if (preg_match($pattern, $transcript, $matches)) {
            $name = trim($matches[1]);
            echo "   ✅ Found name with manual pattern: {$name}\n";

            $latestCall->customer_name = $name;
            $latestCall->save();
            echo "   ✅ Call updated with customer_name: {$name}\n";
        } else {
            echo "   ❌ Still no name found\n";
        }
    }
}

// Verify update
$updatedCall = Call::find($latestCall->id);
echo "\n📊 VERIFICATION:\n";
echo "   Customer name after update: " . ($updatedCall->customer_name ?: 'NULL') . "\n";

echo "\n✅ Process complete!\n";