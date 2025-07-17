<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ANALYZE Queue Problem ===\n\n";

// 1. Clear old test job
echo "1. Clearing old test job from queue:\n";
$redis = app('redis');
$oldJob = $redis->lindex("queues:default", 0);
if ($oldJob) {
    $job = json_decode($oldJob, true);
    if (isset($job['displayName']) && $job['displayName'] === 'TestJob') {
        $redis->lpop("queues:default");
        echo "   ✅ Old TestJob removed\n";
    }
}

// 2. Check how Business Portal sends emails
echo "\n2. Checking Business Portal email method:\n";

// Look at the controller
$controllerPath = app_path('Http/Controllers/Portal/Api/CallApiController.php');
if (file_exists($controllerPath)) {
    $content = file_get_contents($controllerPath);
    
    // Check if it uses queue() or send()
    if (str_contains($content, '->queue(')) {
        echo "   ✅ Business Portal uses queue() method\n";
    }
    if (str_contains($content, '->send(')) {
        echo "   ✅ Business Portal uses send() method\n";
    }
}

// 3. Check mail configuration
echo "\n3. Mail Configuration:\n";
echo "   QUEUE_CONNECTION: " . env('QUEUE_CONNECTION') . "\n";
echo "   Mail Queue: " . config('mail.queue') . "\n";
echo "   Mail Mailers: " . json_encode(config('mail.mailers')) . "\n";

// 4. Test the exact Business Portal flow
echo "\n4. Testing exact Business Portal flow:\n";

$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(227);
if ($call) {
    // Set company context like Business Portal does
    if ($call->company_id) {
        app()->instance('current_company_id', $call->company_id);
    }
    
    // Create service instance
    $exportService = app(\App\Services\CallExportService::class);
    
    try {
        // Generate CSV like Business Portal
        $csvData = $exportService->exportSingleCall($call);
        echo "   ✅ CSV generated successfully\n";
        
        // Check the mail sending
        $mail = new \App\Mail\CallSummaryEmail(
            $call,
            true,
            true,
            'Business Portal Flow Test - ' . now()->format('H:i:s'),
            'internal'
        );
        
        // Send via queue
        \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->queue($mail);
        
        echo "   ✅ Email queued\n";
        
        // Check queue immediately
        sleep(1);
        $defaultQueue = $redis->llen("queues:default");
        $emailsQueue = $redis->llen("queues:emails");
        
        echo "   Default queue: $defaultQueue jobs\n";
        echo "   Emails queue: $emailsQueue jobs\n";
        
        // Process queue manually
        echo "\n5. Processing queue manually:\n";
        $output = shell_exec('php artisan queue:work --once --queue=default,emails 2>&1');
        echo $output . "\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}

echo "\n=== DIAGNOSE ===\n";
echo "Das Problem könnte sein:\n";
echo "1. Queue wird nicht richtig verarbeitet\n";
echo "2. Mail Job landet in falscher Queue\n";
echo "3. Horizon Konfiguration stimmt nicht\n\n";

echo "=== SOFORT-LÖSUNG ===\n";
echo "Führen Sie aus:\n";
echo "php artisan queue:restart\n";
echo "php artisan horizon:terminate\n";
echo "php artisan horizon\n";