<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Checking Webhook Table Schema ===\n\n";

// Check retell_webhooks columns
echo "1. Schema of retell_webhooks table:\n";
if (Schema::hasTable('retell_webhooks')) {
    $columns = Schema::getColumnListing('retell_webhooks');
    echo "   Columns: " . implode(', ', $columns) . "\n\n";
    
    // Get some sample data
    echo "2. Recent webhook entries:\n";
    try {
        $webhooks = DB::table('retell_webhooks')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        if ($webhooks->isEmpty()) {
            echo "   No webhook entries found\n";
        } else {
            foreach ($webhooks as $webhook) {
                echo "   ---\n";
                foreach ($webhook as $key => $value) {
                    if (is_string($value) && strlen($value) > 50) {
                        $value = substr($value, 0, 50) . '...';
                    }
                    echo "   $key: $value\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "   Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ Table does not exist\n";
}

// Check if MCP is actually storing webhooks
echo "\n3. Checking latest webhook by timestamp:\n";
try {
    $latestWebhook = DB::table('retell_webhooks')
        ->orderBy('created_at', 'desc')
        ->first();
        
    if ($latestWebhook) {
        $createdAt = new \DateTime($latestWebhook->created_at);
        $now = new \DateTime();
        $diff = $now->diff($createdAt);
        
        echo "   Latest webhook: " . $latestWebhook->created_at . "\n";
        echo "   Time ago: ";
        if ($diff->days > 0) {
            echo $diff->days . " days ";
        }
        if ($diff->h > 0) {
            echo $diff->h . " hours ";
        }
        echo $diff->i . " minutes ago\n";
        
        if ($diff->days == 0 && $diff->h == 0 && $diff->i < 5) {
            echo "   ✅ Webhooks are being received recently!\n";
        } else {
            echo "   ⚠️  No recent webhooks (last one was " . ($diff->days > 0 ? $diff->days . " days" : ($diff->h > 0 ? $diff->h . " hours" : $diff->i . " minutes")) . " ago)\n";
        }
    } else {
        echo "   No webhooks found in table\n";
    }
} catch (\Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";