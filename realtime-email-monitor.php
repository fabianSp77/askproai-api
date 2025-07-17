<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== REALTIME EMAIL MONITORING ===\n\n";

// 1. Monitor queue in realtime
$redis = app('redis');

echo "Starting monitoring... Press Ctrl+C to stop\n";
echo "Please send an email from the Business Portal NOW!\n\n";

$lastCheck = [];
$iteration = 0;

while (true) {
    $iteration++;
    $timestamp = date('H:i:s');
    
    // Check all queues
    $queues = ['default', 'emails', 'high', 'high:notify'];
    $currentState = [];
    $hasChanges = false;
    
    foreach ($queues as $queue) {
        $count = $redis->llen("queues:$queue");
        $currentState[$queue] = $count;
        
        if (!isset($lastCheck[$queue])) {
            $lastCheck[$queue] = $count;
        }
        
        if ($count != $lastCheck[$queue]) {
            $hasChanges = true;
            $diff = $count - $lastCheck[$queue];
            echo "[$timestamp] QUEUE CHANGE: $queue " . 
                 ($diff > 0 ? "+$diff" : "$diff") . 
                 " (now: $count)\n";
            
            // If jobs were added, show details
            if ($diff > 0 && $count > 0) {
                $job = $redis->lindex("queues:$queue", 0);
                $jobData = json_decode($job, true);
                if ($jobData) {
                    echo "  → Job: " . ($jobData['displayName'] ?? 'unknown') . "\n";
                    echo "  → ID: " . ($jobData['id'] ?? 'unknown') . "\n";
                    echo "  → Created: " . date('H:i:s', $jobData['pushedAt'] ?? 0) . "\n";
                    
                    // Check if it's an email job
                    if (str_contains($jobData['displayName'] ?? '', 'Mail')) {
                        echo "  → ✉️  EMAIL JOB DETECTED!\n";
                        
                        // Decode the data
                        if (isset($jobData['data']['command'])) {
                            $command = unserialize($jobData['data']['command']);
                            echo "  → Class: " . get_class($command) . "\n";
                        }
                    }
                }
            }
        }
        
        $lastCheck[$queue] = $count;
    }
    
    // Check failed jobs
    $failedCount = \DB::table('failed_jobs')->count();
    if (!isset($lastCheck['failed'])) {
        $lastCheck['failed'] = $failedCount;
    }
    
    if ($failedCount != $lastCheck['failed']) {
        echo "[$timestamp] FAILED JOBS: " . ($failedCount - $lastCheck['failed']) . " new failures!\n";
        
        // Show last failure
        $lastFailed = \DB::table('failed_jobs')->orderBy('failed_at', 'desc')->first();
        if ($lastFailed) {
            echo "  → Failed at: " . $lastFailed->failed_at . "\n";
            echo "  → Queue: " . $lastFailed->queue . "\n";
            echo "  → Error: " . substr($lastFailed->exception, 0, 100) . "...\n";
        }
        
        $lastCheck['failed'] = $failedCount;
    }
    
    // Check recent activities
    $recentActivity = \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('activity_type', 'email_sent')
        ->where('created_at', '>', now()->subMinutes(1))
        ->orderBy('created_at', 'desc')
        ->first();
    
    $activityKey = 'last_activity_' . ($recentActivity->id ?? 0);
    if ($recentActivity && !isset($lastCheck[$activityKey])) {
        echo "[$timestamp] NEW EMAIL ACTIVITY: Call #" . $recentActivity->call_id . "\n";
        $metadata = json_decode($recentActivity->metadata, true);
        if (isset($metadata['recipients'])) {
            echo "  → Recipients: " . implode(', ', $metadata['recipients']) . "\n";
        }
        $lastCheck[$activityKey] = true;
    }
    
    // Status line
    if ($iteration % 10 == 0) {
        echo "[$timestamp] Monitoring... Queues: " . 
             implode(', ', array_map(fn($q, $c) => "$q:$c", array_keys($currentState), $currentState)) . 
             "\r";
    }
    
    usleep(100000); // Check every 0.1 seconds
}