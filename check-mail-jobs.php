<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check if there are any mail jobs
$jobs = \DB::table('jobs')->get();
echo "Total jobs in queue: " . $jobs->count() . "\n\n";

foreach ($jobs as $job) {
    $payload = json_decode($job->payload, true);
    echo "Job ID: {$job->id}\n";
    echo "Queue: {$job->queue}\n";
    echo "Created: " . date('Y-m-d H:i:s', $job->created_at) . "\n";
    echo "Attempts: {$job->attempts}\n";
    echo "Job Type: " . ($payload['displayName'] ?? 'Unknown') . "\n";
    
    // Check if it's a mail job
    if (isset($payload['data']['commandName']) && strpos($payload['data']['commandName'], 'Mail') !== false) {
        echo "This is a MAIL job!\n";
        $command = unserialize($payload['data']['command']);
        if (method_exists($command, 'envelope')) {
            $envelope = $command->envelope();
            echo "To: " . implode(', ', array_map(function($to) { return $to->address; }, $envelope->to)) . "\n";
        }
    }
    
    echo "---\n\n";
}

// Check failed jobs
$failedJobs = \DB::table('failed_jobs')->latest()->limit(5)->get();
if ($failedJobs->count() > 0) {
    echo "\nFailed Jobs:\n";
    foreach ($failedJobs as $job) {
        $payload = json_decode($job->payload, true);
        echo "Failed Job ID: {$job->id}\n";
        echo "Job Type: " . ($payload['displayName'] ?? 'Unknown') . "\n";
        echo "Failed at: {$job->failed_at}\n";
        echo "Exception: " . substr($job->exception, 0, 500) . "\n";
        echo "---\n\n";
    }
}

// Try to process one job manually
if ($jobs->count() > 0 && in_array('--process', $argv ?? [])) {
    echo "\nAttempting to process first job manually...\n";
    $firstJob = $jobs->first();
    
    try {
        $job = new \Illuminate\Queue\Jobs\DatabaseJob(
            app(),
            app(\Illuminate\Queue\DatabaseQueue::class),
            $firstJob,
            app(\Illuminate\Database\ConnectionInterface::class),
            $firstJob->queue
        );
        
        $job->fire();
        echo "✅ Job processed successfully!\n";
    } catch (\Exception $e) {
        echo "❌ Error processing job: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}