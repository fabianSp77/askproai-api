<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEBUG Queue Mail Driver ===\n\n";

// 1. Create a debug mail driver
echo "1. Creating debug mail driver:\n";

\Illuminate\Support\Facades\Mail::extend('debug', function ($app) {
    return new class {
        public function send($view, array $data = [], $callback = null)
        {
            echo "[DEBUG DRIVER] send() called\n";
            \Log::info('[DEBUG DRIVER] send() called', [
                'view' => is_object($view) ? get_class($view) : $view,
                'timestamp' => now()->toIso8601String()
            ]);
        }
    };
});

// 2. Override mail config temporarily
echo "2. Setting mail driver to debug:\n";
config(['mail.default' => 'debug']);
echo "   Mail driver: " . config('mail.default') . "\n";

// 3. Test queue with debug driver
$callId = 228;
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);
app()->instance('current_company_id', $call->company_id);

echo "\n3. Queueing email with debug driver:\n";

try {
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->queue(new \App\Mail\CallSummaryEmail(
        $call,
        true,
        false,
        'Debug Driver Test - ' . now()->format('H:i:s'),
        'internal'
    ));
    
    echo "   ✅ Queued\n";
    
    // Process
    echo "\n4. Processing queue:\n";
    $output = shell_exec('php artisan queue:work --once --queue=default 2>&1');
    echo $output . "\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 4. Reset to resend and test
echo "\n5. Resetting to resend driver:\n";
config(['mail.default' => 'resend']);

// 5. Check if Mailable is using wrong connection
echo "\n6. Checking CallSummaryEmail queue settings:\n";
$reflection = new \ReflectionClass(\App\Mail\CallSummaryEmail::class);
$properties = $reflection->getDefaultProperties();

echo "   Queue connection: " . ($properties['connection'] ?? 'default') . "\n";
echo "   Queue name: " . ($properties['queue'] ?? 'default') . "\n";

// 6. Manually process a queued email
echo "\n7. Manual queue job processing:\n";

// Queue an email
$email = new \App\Mail\CallSummaryEmail(
    $call,
    true,
    false,
    'Manual Process Test - ' . now()->format('H:i:s'),
    'internal'
);

// Get the queued job
$job = new \Illuminate\Mail\SendQueuedMailable($email);
$job->to = [['name' => null, 'address' => 'fabianspitzer@icloud.com']];

echo "   Processing SendQueuedMailable manually:\n";

try {
    // Get the Laravel app instance
    $handler = new \Illuminate\Queue\CallQueuedHandler(
        app(\Illuminate\Contracts\Bus\Dispatcher::class),
        app(\Illuminate\Contracts\Container\Container::class)
    );
    
    // Process the job
    $handler->call($job);
    
    echo "   ✅ Manual processing completed\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// 7. Check logs
echo "\n8. Recent log entries:\n";
$log = file_get_contents(storage_path('logs/laravel.log'));
$lines = explode("\n", $log);
$recentLines = array_slice($lines, -10);

foreach ($recentLines as $line) {
    if (str_contains($line, 'DEBUG DRIVER') || str_contains($line, 'ResendTransport')) {
        echo "   " . $line . "\n";
    }
}

echo "\n=== ANALYSIS ===\n";
echo "If DEBUG DRIVER logs appear, the mail system is working.\n";
echo "If not, the queue job is not calling the mail driver at all.\n";