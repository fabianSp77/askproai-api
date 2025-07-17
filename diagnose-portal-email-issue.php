<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DIAGNOSE Business Portal Email Issue ===\n\n";

// 1. Check Horizon status
echo "1. Horizon Status:\n";
$horizonStatus = shell_exec('php artisan horizon:status 2>&1');
echo "   " . trim($horizonStatus) . "\n\n";

// 2. Check email queue
$redis = app('redis');
echo "2. Queue Status:\n";
$queues = ['default', 'emails', 'high:notify'];
foreach ($queues as $queue) {
    $count = $redis->llen("queues:$queue");
    echo "   $queue: $count jobs\n";
}
echo "\n";

// 3. Check failed jobs
$failedCount = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
echo "3. Failed Jobs: $failedCount\n";
if ($failedCount > 0) {
    $latestFailed = \Illuminate\Support\Facades\DB::table('failed_jobs')
        ->orderBy('failed_at', 'desc')
        ->first();
    if ($latestFailed) {
        echo "   Latest: " . $latestFailed->failed_at . " - " . $latestFailed->exception . "\n";
    }
}
echo "\n";

// 4. Check email configuration
echo "4. Email Configuration:\n";
echo "   Driver: " . config('mail.default') . "\n";
echo "   From: " . config('mail.from.address') . "\n";
echo "   Resend API: " . (config('services.resend.key') ? 'Configured' : 'Missing') . "\n\n";

// 5. Check recent activities
echo "5. Recent Email Activities (last 5):\n";
$activities = \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('activity_type', 'email_sent')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($activities as $activity) {
    echo "   - " . $activity->created_at->format('Y-m-d H:i:s') . 
         " Call #" . $activity->call_id . 
         " to " . ($activity->details['recipients'] ?? 'unknown') . "\n";
}
echo "\n";

// 6. Test a real portal user session
echo "6. Testing Portal User Session:\n";
$portalUser = \App\Models\PortalUser::first();
if ($portalUser) {
    echo "   User: " . $portalUser->email . "\n";
    echo "   Company: " . $portalUser->company_id . "\n";
    
    // Check if user can authenticate
    if (\Illuminate\Support\Facades\Auth::guard('portal')->attempt([
        'email' => $portalUser->email,
        'password' => 'password' // Default test password
    ])) {
        echo "   ✅ Authentication successful\n";
    } else {
        echo "   ❌ Authentication failed\n";
    }
} else {
    echo "   ❌ No portal user found\n";
}
echo "\n";

// 7. Check logs for errors
echo "7. Recent Errors in Logs:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = shell_exec("tail -50 $logFile | grep -i 'error\\|exception\\|failed' | tail -5");
    if ($logs) {
        echo $logs;
    } else {
        echo "   No recent errors found\n";
    }
}

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. Make sure Horizon is running: php artisan horizon\n";
echo "2. Clear and restart queues if needed: php artisan queue:restart\n";
echo "3. Check browser console for JavaScript errors\n";
echo "4. Verify CSRF token is being sent in requests\n";
echo "5. Test with a different email address to avoid duplicate blocks\n";