<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== MONITOR Business Portal E-Mail Flow ===\n\n";

// 1. Check if Queue workers are actually processing emails
echo "1. Queue Worker Status:\n";
$horizonStatus = shell_exec('php artisan horizon:status 2>&1');
echo "   Horizon: " . trim($horizonStatus) . "\n";

$workers = shell_exec('ps aux | grep -E "queue:work|horizon" | grep -v grep');
echo "   Active Workers:\n" . ($workers ?: "   KEINE WORKER GEFUNDEN!\n") . "\n";

// 2. Check Queue Configuration
echo "2. Queue Configuration:\n";
echo "   Default Queue: " . config('queue.default') . "\n";
echo "   Mail Queue: " . (config('mail.queue') ?: 'default') . "\n\n";

// 3. Check what happens when email is queued
echo "3. Simuliere Business Portal E-Mail:\n";

// Get call
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(227);
if (!$call) {
    echo "❌ Call 227 nicht gefunden!\n";
    exit(1);
}

// Set context
app()->instance('current_company_id', $call->company_id);

// Clear activities
\App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', 227)
    ->where('activity_type', 'email_sent')
    ->where('created_at', '>', now()->subMinutes(5))
    ->delete();

try {
    echo "   Stelle E-Mail in Queue...\n";
    
    // Monitor Redis before
    $redis = app('redis');
    $beforeCounts = [];
    foreach (['default', 'high', 'emails', 'low'] as $queue) {
        $beforeCounts[$queue] = $redis->llen("queues:{$queue}");
    }
    
    // Queue email like Business Portal does
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->queue(new \App\Mail\CallSummaryEmail(
        $call,
        true,
        true,
        'Business Portal Monitor Test - ' . now()->format('H:i:s'),
        'internal'
    ));
    
    echo "   ✅ E-Mail in Queue gestellt\n\n";
    
    // Monitor Redis after
    echo "4. Queue Status NACH dem Einstellen:\n";
    foreach (['default', 'high', 'emails', 'low'] as $queue) {
        $afterCount = $redis->llen("queues:{$queue}");
        $diff = $afterCount - $beforeCounts[$queue];
        echo "   - $queue: $afterCount Jobs";
        if ($diff > 0) {
            echo " (+$diff NEU)";
        }
        echo "\n";
        
        // Show job details if new
        if ($diff > 0) {
            $lastJob = $redis->lindex("queues:{$queue}", -1);
            if ($lastJob) {
                $job = json_decode($lastJob, true);
                echo "     Letzter Job: " . ($job['displayName'] ?? 'Unknown') . "\n";
                echo "     Job UUID: " . ($job['uuid'] ?? 'N/A') . "\n";
            }
        }
    }
    
    // Wait and check
    echo "\n5. Warte 3 Sekunden...\n";
    sleep(3);
    
    echo "\n6. Queue Status NACH 3 Sekunden:\n";
    $anyJobsLeft = false;
    foreach (['default', 'high', 'emails', 'low'] as $queue) {
        $count = $redis->llen("queues:{$queue}");
        if ($count > 0) {
            $anyJobsLeft = true;
            echo "   - $queue: $count Jobs ⚠️ NICHT VERARBEITET\n";
            
            // Get job details
            $job = json_decode($redis->lindex("queues:{$queue}", 0), true);
            if ($job) {
                echo "     Job: " . ($job['displayName'] ?? 'Unknown') . "\n";
                echo "     Pushed At: " . date('Y-m-d H:i:s', $job['pushedAt'] ?? 0) . "\n";
            }
        } else {
            echo "   - $queue: 0 Jobs ✅\n";
        }
    }
    
    if ($anyJobsLeft) {
        echo "\n⚠️  PROBLEM: Jobs werden nicht verarbeitet!\n\n";
        
        echo "7. Versuche manuell zu verarbeiten:\n";
        $output = shell_exec('php artisan queue:work --once --queue=default,high,emails 2>&1');
        echo $output . "\n";
        
        // Check for errors
        if (str_contains($output, 'ERROR') || str_contains($output, 'Exception')) {
            echo "\n❌ FEHLER beim Verarbeiten!\n";
        }
    } else {
        echo "\n✅ Alle Jobs wurden verarbeitet\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== DIAGNOSE ===\n";
if (!$workers) {
    echo "❌ KEINE Queue Worker laufen!\n";
    echo "   → Starten Sie Horizon: php artisan horizon\n";
} elseif ($anyJobsLeft) {
    echo "❌ Queue Worker verarbeiten keine Jobs!\n";
    echo "   → Horizon neu starten: php artisan horizon:terminate\n";
} else {
    echo "✅ Queue System funktioniert\n";
}