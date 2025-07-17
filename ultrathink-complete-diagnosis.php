<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ULTRATHINK COMPLETE EMAIL DIAGNOSIS ===\n";
echo "Analyzing EVERY component in the email flow...\n\n";

// ========================================
// 1. DATABASE & TRANSACTION ANALYSIS
// ========================================
echo "1. DATABASE & TRANSACTION ANALYSIS:\n";
$inTransaction = \DB::transactionLevel() > 0;
echo "   Currently in transaction: " . ($inTransaction ? "YES (PROBLEM!)" : "NO") . "\n";
echo "   Transaction level: " . \DB::transactionLevel() . "\n";

// Check if jobs table exists
try {
    $jobsCount = \DB::table('jobs')->count();
    echo "   Jobs table exists: YES (count: $jobsCount)\n";
} catch (\Exception $e) {
    echo "   Jobs table exists: NO\n";
}

// Check failed jobs
try {
    $failedJobs = \DB::table('failed_jobs')->orderBy('failed_at', 'desc')->limit(5)->get();
    echo "   Failed jobs (last 5):\n";
    foreach ($failedJobs as $job) {
        echo "      - " . $job->failed_at . ": " . $job->queue . " - " . substr($job->exception, 0, 50) . "...\n";
    }
} catch (\Exception $e) {
    echo "   Failed jobs table: NOT FOUND\n";
}

// ========================================
// 2. REDIS CONFIGURATION CHECK
// ========================================
echo "\n2. REDIS CONFIGURATION CHECK:\n";
$redisConfig = config('database.redis');
echo "   Default connection: " . ($redisConfig['default']['url'] ?? 'tcp://127.0.0.1:6379') . "\n";
echo "   Database: " . ($redisConfig['default']['database'] ?? '0') . "\n";
echo "   Prefix: " . ($redisConfig['options']['prefix'] ?? 'laravel_database_') . "\n";

// CRITICAL: Check for serializer settings
if (isset($redisConfig['options']['serializer']) || isset($redisConfig['default']['options']['serializer'])) {
    echo "   ❌ CRITICAL: Redis serializer is set! This causes queue issues!\n";
} else {
    echo "   ✅ No Redis serializer configured (good)\n";
}

// Test Redis connection
try {
    $redis = app('redis');
    $redis->ping();
    echo "   ✅ Redis connection: OK\n";
    
    // Check queue keys
    $queueKeys = $redis->keys('*queue*');
    echo "   Queue-related keys: " . count($queueKeys) . "\n";
    foreach (array_slice($queueKeys, 0, 5) as $key) {
        echo "      - $key\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Redis error: " . $e->getMessage() . "\n";
}

// ========================================
// 3. MAIL TRANSPORT ANALYSIS
// ========================================
echo "\n3. MAIL TRANSPORT ANALYSIS:\n";
$mailConfig = config('mail');
$defaultMailer = $mailConfig['default'];
echo "   Default mailer: $defaultMailer\n";

if (isset($mailConfig['mailers'][$defaultMailer])) {
    $mailerConfig = $mailConfig['mailers'][$defaultMailer];
    echo "   Transport: " . ($mailerConfig['transport'] ?? 'unknown') . "\n";
    echo "   Queue: " . ($mailerConfig['queue'] ?? 'NOT SET') . "\n";
}

// Check if ResendTransport is registered
$providers = app()->getProviders(\Illuminate\Support\ServiceProvider::class);
$hasMailExtension = false;
foreach ($providers as $provider) {
    $reflection = new ReflectionClass($provider);
    $content = file_get_contents($reflection->getFileName());
    if (str_contains($content, "Mail::extend") && str_contains($content, "resend")) {
        $hasMailExtension = true;
        echo "   ✅ Resend transport registered in: " . $reflection->getShortName() . "\n";
        break;
    }
}
if (!$hasMailExtension) {
    echo "   ❌ Resend transport NOT registered in any service provider!\n";
}

// ========================================
// 4. QUEUE CONFIGURATION ANALYSIS
// ========================================
echo "\n4. QUEUE CONFIGURATION:\n";
$queueConfig = config('queue');
echo "   Default connection: " . $queueConfig['default'] . "\n";
echo "   Failed table: " . ($queueConfig['failed']['table'] ?? 'failed_jobs') . "\n";

$redisQueue = $queueConfig['connections']['redis'] ?? null;
if ($redisQueue) {
    echo "   Redis queue config:\n";
    echo "      Connection: " . ($redisQueue['connection'] ?? 'default') . "\n";
    echo "      Queue: " . ($redisQueue['queue'] ?? 'default') . "\n";
    echo "      Retry after: " . ($redisQueue['retry_after'] ?? 90) . " seconds\n";
    echo "      Block for: " . ($redisQueue['block_for'] ?? 'null') . "\n";
}

// ========================================
// 5. HORIZON CONFIGURATION
// ========================================
echo "\n5. HORIZON CONFIGURATION:\n";
$horizonConfig = config('horizon');
$env = app()->environment();
echo "   Environment: $env\n";

if (isset($horizonConfig['environments'][$env])) {
    $envConfig = $horizonConfig['environments'][$env];
    foreach ($envConfig as $supervisor => $config) {
        if (in_array('emails', (array)($config['queue'] ?? []))) {
            echo "   Email supervisor: $supervisor\n";
            echo "      Queue: " . implode(', ', (array)$config['queue']) . "\n";
            echo "      Processes: " . ($config['maxProcesses'] ?? 'not set') . "\n";
            echo "      Tries: " . ($config['tries'] ?? 'not set') . "\n";
            echo "      Timeout: " . ($config['timeout'] ?? 'not set') . "\n";
        }
    }
}

// ========================================
// 6. JOB CLASS ANALYSIS
// ========================================
echo "\n6. JOB CLASS ANALYSIS:\n";
$jobClass = 'App\\Jobs\\SendCallSummaryEmailJob';
if (class_exists($jobClass)) {
    echo "   ✅ SendCallSummaryEmailJob exists\n";
    $reflection = new ReflectionClass($jobClass);
    
    // Check interfaces
    $interfaces = $reflection->getInterfaceNames();
    echo "   Implements ShouldQueue: " . (in_array('Illuminate\Contracts\Queue\ShouldQueue', $interfaces) ? "YES" : "NO") . "\n";
    
    // Check traits
    $traits = $reflection->getTraitNames();
    echo "   Uses SerializesModels: " . (in_array('Illuminate\Queue\SerializesModels', $traits) ? "YES" : "NO") . "\n";
    echo "   Uses Queueable: " . (in_array('Illuminate\Bus\Queueable', $traits) ? "YES" : "NO") . "\n";
    
    // Check for afterCommit
    $content = file_get_contents($reflection->getFileName());
    if (str_contains($content, 'afterCommit')) {
        echo "   Uses afterCommit: YES\n";
    } else {
        echo "   Uses afterCommit: NO (might be an issue)\n";
    }
} else {
    echo "   ❌ SendCallSummaryEmailJob NOT FOUND!\n";
}

// ========================================
// 7. SESSION & CORS ANALYSIS
// ========================================
echo "\n7. SESSION & CORS FOR CROSS-DOMAIN:\n";
echo "   Session domain: " . config('session.domain') . "\n";
echo "   Session secure: " . (config('session.secure') ? "YES" : "NO") . "\n";
echo "   Session same_site: " . config('session.same_site') . "\n";

if (config('session.same_site') === 'strict' || config('session.same_site') === 'lax') {
    echo "   ⚠️  WARNING: same_site=" . config('session.same_site') . " may block cross-domain requests!\n";
}

echo "\n   CORS config:\n";
echo "   Supports credentials: " . (config('cors.supports_credentials') ? "YES" : "NO") . "\n";
echo "   Allowed origins: " . json_encode(config('cors.allowed_origins')) . "\n";

// ========================================
// 8. REAL-TIME TEST
// ========================================
echo "\n8. REAL-TIME EMAIL TEST:\n";
$callId = 258;
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);

if ($call) {
    app()->instance('current_company_id', $call->company_id);
    
    // Test 1: Direct mail send
    echo "   Test 1 - Direct send:\n";
    try {
        $testEmail = 'test-direct-' . time() . '@example.com';
        \Illuminate\Support\Facades\Mail::to($testEmail)->send(
            new \App\Mail\CallSummaryEmail($call, true, true, 'Direct test', 'internal')
        );
        echo "      ✅ Direct send completed\n";
    } catch (\Exception $e) {
        echo "      ❌ Error: " . $e->getMessage() . "\n";
    }
    
    // Test 2: Queue with job
    echo "\n   Test 2 - Job dispatch:\n";
    try {
        $redis = app('redis');
        $before = $redis->llen('queues:emails');
        
        \App\Jobs\SendCallSummaryEmailJob::dispatch(
            $callId,
            ['test-job-' . time() . '@example.com'],
            true,
            true,
            'Job test',
            'internal'
        );
        
        $after = $redis->llen('queues:emails');
        echo "      Queue before: $before, after: $after\n";
        echo "      " . ($after > $before ? "✅ Job queued" : "❌ Job NOT queued") . "\n";
        
        if ($after > $before) {
            // Check job details
            $job = $redis->lindex('queues:emails', -1);
            $jobData = json_decode($job, true);
            echo "      Job payload size: " . strlen($job) . " bytes\n";
            echo "      Job created at: " . date('Y-m-d H:i:s', $jobData['pushedAt'] ?? 0) . "\n";
        }
    } catch (\Exception $e) {
        echo "      ❌ Error: " . $e->getMessage() . "\n";
    }
}

// ========================================
// 9. LOG ANALYSIS
// ========================================
echo "\n9. RECENT LOG ANALYSIS:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = shell_exec("tail -50 $logFile | grep -E 'mail|email|queue|resend' -i | tail -10");
    if ($logs) {
        echo $logs;
    } else {
        echo "   No recent email-related logs\n";
    }
}

// ========================================
// 10. RECOMMENDATIONS
// ========================================
echo "\n10. CRITICAL ISSUES FOUND:\n";
$issues = [];

if (isset($redisConfig['options']['serializer'])) {
    $issues[] = "Redis serializer is set - remove it from config/database.php";
}

if (!$hasMailExtension) {
    $issues[] = "Resend transport not registered - add Mail::extend in AppServiceProvider";
}

if (config('session.same_site') !== 'none') {
    $issues[] = "Session same_site should be 'none' for cross-domain";
}

if (!config('cors.supports_credentials')) {
    $issues[] = "CORS supports_credentials must be true";
}

if (empty($issues)) {
    echo "   No critical configuration issues found\n";
} else {
    foreach ($issues as $i => $issue) {
        echo "   " . ($i + 1) . ". ❌ $issue\n";
    }
}

echo "\n=== END ULTRATHINK DIAGNOSIS ===\n";