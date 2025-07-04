<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Redis;

echo "=== BEREINIGE FAILED JOBS ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n\n";

// Count before
$countBefore = Redis::zcard('askproaifailed_jobs');
echo "Failed Jobs vorher: $countBefore\n";

// Delete failed jobs
Redis::del('askproaifailed_jobs');

// Delete all failed job details
$failedKeys = Redis::keys('askproaifailed:*');
if (count($failedKeys) > 0) {
    foreach ($failedKeys as $key) {
        Redis::del($key);
    }
    echo "Gelöschte Detail-Keys: " . count($failedKeys) . "\n";
}

// Count after
$countAfter = Redis::zcard('askproaifailed_jobs');
echo "Failed Jobs nachher: $countAfter\n";

echo "\n✅ Failed Jobs bereinigt!\n";